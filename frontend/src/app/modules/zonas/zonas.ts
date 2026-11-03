import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../services/auth';
import { DatosZona, ZonaComun, ZonasService } from '../../services/zonas';

@Component({
  selector: 'app-zonas',
  imports: [CurrencyPipe, FormsModule, RouterLink],
  templateUrl: './zonas.html',
  styleUrl: './zonas.css',
})
export class Zonas implements OnInit {
  private readonly servicio = inject(ZonasService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  zonas: ZonaComun[] = [];
  mensaje = '';
  error = false;
  cargando = true;
  guardando = false;
  pagina = 1;
  total = 0;
  editandoId: number | null = null;
  formulario: DatosZona = this.formularioVacio();

  get totalPaginas(): number {
    return Math.max(1, Math.ceil(this.total / 20));
  }

  ngOnInit(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        if (respuesta.user?.rol !== 'Administrador') {
          void this.router.navigateByUrl(respuesta.user ? '/inicio' : '/login');
          return;
        }
        this.cargarZonas();
      },
      error: () => void this.router.navigateByUrl('/login'),
    });
  }

  cargarZonas(): void {
    this.cargando = true;
    this.servicio.listar(this.pagina).subscribe({
      next: (respuesta) => {
        this.zonas = respuesta.items;
        this.total = respuesta.total;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar las zonas comunes.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  guardar(): void {
    if (this.guardando) return;
    this.guardando = true;
    this.mensaje = '';
    this.error = false;
    const esNueva = this.editandoId === null;
    const solicitud = this.editandoId === null
      ? this.servicio.crear(this.formulario)
      : this.servicio.actualizar(this.editandoId, this.formulario);

    solicitud.subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.formulario = this.formularioVacio();
        this.editandoId = null;
        if (esNueva) this.pagina = 1;
        this.guardando = false;
        this.cargarZonas();
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo guardar la zona común.');
        this.guardando = false;
        this.cdr.markForCheck();
      },
    });
  }

  editar(zona: ZonaComun): void {
    this.editandoId = zona.id;
    this.formulario = {
      nombre: zona.nombre,
      aforo: Number(zona.aforo),
      hora_apertura: zona.hora_apertura.slice(0, 5),
      hora_cierre: zona.hora_cierre.slice(0, 5),
      costo: Number(zona.costo),
      requiere_deposito: Number(zona.requiere_deposito) === 1,
    };
    this.mensaje = '';
    this.error = false;
  }

  cancelarEdicion(): void {
    this.editandoId = null;
    this.formulario = this.formularioVacio();
  }

  eliminar(zona: ZonaComun): void {
    if (!window.confirm(`¿Desea inactivar la zona "${zona.nombre}"?`)) return;
    this.servicio.eliminar(zona.id).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.error = false;
        if (this.zonas.length === 1 && this.pagina > 1) this.pagina--;
        this.cargarZonas();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo inactivar la zona común.');
        this.cdr.markForCheck();
      },
    });
  }

  cambiarPagina(delta: number): void {
    const ultimaPagina = this.totalPaginas;
    const nuevaPagina = this.pagina + delta;
    if (nuevaPagina < 1 || nuevaPagina > ultimaPagina) return;
    this.pagina = nuevaPagina;
    this.cargarZonas();
  }

  private mostrarError(respuesta: HttpErrorResponse, alternativa: string): void {
    this.mensaje = respuesta.error?.message ?? alternativa;
    this.error = true;
  }

  private formularioVacio(): DatosZona {
    return {
      nombre: '',
      aforo: 1,
      hora_apertura: '08:00',
      hora_cierre: '22:00',
      costo: 0,
      requiere_deposito: false,
    };
  }
}
