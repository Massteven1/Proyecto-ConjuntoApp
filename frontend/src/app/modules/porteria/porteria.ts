import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService, AuthUser } from '../../services/auth';
import { PorteriaService } from '../../services/porteria';

@Component({
  selector: 'app-porteria',
  imports: [FormsModule, RouterLink],
  templateUrl: './porteria.html',
  styleUrl: './porteria.css',
})
export class Porteria implements OnInit {
  private readonly servicio = inject(PorteriaService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  documento = '';
  resultado: any = null;
  usuario: AuthUser | null = null;
  dentro: any[] = [];
  mensaje = '';
  error = false;
  cargando = false;

  ngOnInit(): void {
    this.cargarSesion();
  }

  cargarSesion(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        this.cdr.detectChanges();

        if (this.usuario) {
          this.cargarDentro();
        } else {
          void this.router.navigateByUrl('/login');
        }
      },
      error: () => {
        void this.router.navigateByUrl('/login');
      },
    });
  }

  buscar(limpiarMensaje: boolean = true): void {
    this.resultado = null;

    this.servicio.buscar(this.documento).subscribe({
      next: (resp: any) => {
        if (resp[0] === 'OK') {
          this.resultado = resp[1];

          if (limpiarMensaje) {
            this.mensaje = '';
            this.error = false;
          }
        } else {
          this.resultado = null;
          this.mensaje = resp[1];
          this.error = true;
        }
        this.cdr.detectChanges();
      },
    });
  }

  ingresar(): void {
    if (this.resultado == null) {
      return;
    }

    const datos = {
      documento: this.resultado.documento,
    };

    this.servicio.ingreso(datos).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';
        this.buscar(false);
        this.cargarDentro();
        this.cdr.detectChanges();
      },
    });
  }

  salir(): void {
    if (this.resultado == null) {
      return;
    }

    const datos = {
      documento: this.resultado.documento,
    };

    this.servicio.salida(datos).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';
        this.buscar(false);
        this.cargarDentro();
        this.cdr.detectChanges();
      },
    });
  }

  cargarDentro(): void {
    this.servicio.dentro().subscribe({
      next: (resp: any) => {
        if (resp[0] === 'OK') {
          this.dentro = resp[1];
        }
        this.cdr.detectChanges();
      },
    });
  }
}
