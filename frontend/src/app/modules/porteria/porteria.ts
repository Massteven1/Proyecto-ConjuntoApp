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
  parqueaderos: any[] = [];
  parqueaderoId = 0;
  nuevoParqueadero = '';
  bitacora: any[] = [];
  filtros = { fecha: '', torre: '', apartamento: '' };
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
          this.cargarParqueaderos();
          this.cargarBitacora();
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
      parqueadero_id: this.parqueaderoId,
    };

    this.servicio.ingreso(datos).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';
        this.parqueaderoId = 0;
        this.buscar(false);
        this.cargarDentro();
        this.cargarParqueaderos();
        this.cargarBitacora();
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
        this.cargarParqueaderos();
        this.cargarBitacora();
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

  cargarParqueaderos(): void {
    this.servicio.parqueaderos().subscribe({
      next: (resp: any) => {
        if (resp[0] === 'OK') {
          this.parqueaderos = resp[1];
        }
        this.cdr.detectChanges();
      },
    });
  }

  get parqueaderosLibres(): any[] {
    return this.parqueaderos.filter((p) => Number(p.ocupado) === 0);
  }

  agregarParqueadero(): void {
    this.servicio.crearParqueadero(this.nuevoParqueadero).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';

        if (resp[0] === 'OK') {
          this.nuevoParqueadero = '';
        }

        this.cargarParqueaderos();
        this.cdr.detectChanges();
      },
    });
  }

  editarParqueadero(parqueadero: any): void {
    const codigo = window.prompt('Nuevo codigo del parqueadero', parqueadero.codigo);

    if (codigo === null) {
      return;
    }

    this.servicio.editarParqueadero(parqueadero.id, codigo).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';
        this.cargarParqueaderos();
        this.cdr.detectChanges();
      },
    });
  }

  eliminarParqueadero(parqueadero: any): void {
    if (!window.confirm(`¿Eliminar el parqueadero ${parqueadero.codigo}?`)) {
      return;
    }

    this.servicio.eliminarParqueadero(parqueadero.id).subscribe({
      next: (resp: any) => {
        this.mensaje = resp[1];
        this.error = resp[0] === 'ERROR';
        this.cargarParqueaderos();
        this.cdr.detectChanges();
      },
    });
  }

  cargarBitacora(): void {
    this.servicio.bitacora(this.filtros.fecha, this.filtros.torre, this.filtros.apartamento).subscribe({
      next: (resp: any) => {
        if (resp[0] === 'OK') {
          this.bitacora = resp[1];
        }
        this.cdr.detectChanges();
      },
    });
  }

  limpiarFiltros(): void {
    this.filtros = { fecha: '', torre: '', apartamento: '' };
    this.cargarBitacora();
  }
}
