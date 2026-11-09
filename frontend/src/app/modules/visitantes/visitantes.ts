import { ChangeDetectorRef, Component, inject, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { AuthService, AuthUser } from '../../services/auth';
import { VisitantesService } from '../../services/visitantes';

@Component({
  selector: 'app-visitantes',
  imports: [FormsModule],
  templateUrl: './visitantes.html',
  styleUrl: './visitantes.css',
})
export class Visitantes implements OnInit {
  autorizaciones: any[] = [];
  apartamentos: any[] = [];
  usuario: AuthUser | null = null;

  mensaje = '';
  error = false;
  guardando = false;

  nuevo = {
    documento: '',
    nombre: '',
    telefono: '',
    apartamento_id: '',
    fecha_visita: '',
    con_vehiculo: false,
    placa: '',
  };

  private readonly servicio = inject(VisitantesService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  ngOnInit(): void {
    this.nuevo.fecha_visita = new Date().toISOString().slice(0, 10);
    this.cargarSesion();
  }

  cargarSesion(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        this.cdr.detectChanges();

        if (this.usuario) {
          if (this.usuario.rol !== 'Residente') {
            void this.router.navigateByUrl('/inicio');
            return;
          }

          this.cargarAutorizaciones();
          this.cargarApartamentos();
        } else {
          void this.router.navigateByUrl('/login');
        }
      },
      error: () => {
        void this.router.navigateByUrl('/login');
      },
    });
  }

  cargarAutorizaciones(): void {
    this.servicio.listar().subscribe({
      next: (resp) => {
        if (resp[0] === 'OK') {
          this.autorizaciones = resp[1];
        }
        this.cdr.detectChanges();
      },
    });
  }

  cargarApartamentos(): void {
    this.servicio.apartamentos().subscribe({
      next: (resp) => {
        if (resp[0] === 'OK') {
          this.apartamentos = resp[1];

          if (this.apartamentos.length === 0) {
            this.mensaje = 'Su usuario no tiene un apartamento asociado';
            this.error = true;
          }
        }
        this.cdr.detectChanges();
      },
    });
  }

  guardar(): void {
    if (this.guardando) {
      return;
    }

    this.guardando = true;

    const datos = {
      documento: this.nuevo.documento,
      nombre: this.nuevo.nombre,
      telefono: this.nuevo.telefono,
      apartamento_id: Number(this.nuevo.apartamento_id),
      fecha_visita: this.nuevo.fecha_visita,
      con_vehiculo: this.nuevo.con_vehiculo ? 1 : 0,
      placa: this.nuevo.placa,
    };

    this.servicio.preautorizar(datos).subscribe({
      next: (resp) => {
        if (resp[0] === 'OK') {
          this.mensaje = resp[1];
          this.error = false;
          this.cargarAutorizaciones();
          this.nuevo.documento = '';
          this.nuevo.nombre = '';
          this.nuevo.telefono = '';
          this.nuevo.placa = '';
        } else {
          this.mensaje = resp[1];
          this.error = true;
        }
        this.cdr.detectChanges();
      },
      error: () => {
        this.guardando = false;
        this.mensaje = 'No se pudo conectar con el servidor';
        this.error = true;
        this.cdr.detectChanges();
      },
      complete: () => {
        this.guardando = false;
      },
    });
  }
}
