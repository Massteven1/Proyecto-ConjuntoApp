import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { CurrencyPipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService, AuthUser } from '../../services/auth';
import {
  ApartamentoReserva,
  ReservaPendiente,
  ReservaPorteria,
  ReservaPropia,
  ReservasService,
  ReservaDia,
  ZonaReserva,
} from '../../services/reservas';

@Component({
  selector: 'app-reservas',
  imports: [CurrencyPipe, FormsModule, RouterLink],
  templateUrl: './reservas.html',
  styleUrl: './reservas.css',
})
export class Reservas implements OnInit {
  private readonly servicio = inject(ReservasService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  usuario: AuthUser | null = null;
  zonas: ZonaReserva[] = [];
  apartamentos: ApartamentoReserva[] = [];
  disponibilidad: ReservaDia[] = [];
  misReservas: ReservaPropia[] = [];
  pendientes: ReservaPendiente[] = [];
  reservasHoy: ReservaPorteria[] = [];
  zonaId = 0;
  apartamentoId = 0;
  fecha = this.fechaLocal(new Date());
  horaInicio = '09:00';
  horaFin = '10:00';
  observaciones = '';
  mensaje = '';
  error = false;
  cargando = true;
  guardando = false;

  ngOnInit(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        if (!this.usuario) {
          void this.router.navigateByUrl('/login');
          return;
        }

        if (this.usuario.rol === 'Residente') {
          this.cargarDisponibilidad();
          this.cargarMisReservas();
        } else if (this.usuario.rol === 'Administrador') {
          this.cargarPendientes();
        } else if (this.usuario.rol === 'Portero') {
          this.cargarReservasHoy();
        } else {
          void this.router.navigateByUrl('/inicio');
        }
        this.cdr.markForCheck();
      },
      error: () => void this.router.navigateByUrl('/login'),
    });
  }

  cargarDisponibilidad(): void {
    this.cargando = true;
    this.servicio.disponibilidad(this.fecha).subscribe({
      next: (respuesta) => {
        this.zonas = respuesta.zonas;
        this.apartamentos = respuesta.apartamentos;
        this.disponibilidad = respuesta.reservas;
        if (!this.zonas.some((zona) => zona.id === this.zonaId)) {
          this.zonaId = this.zonas[0]?.id ?? 0;
        }
        if (!this.apartamentos.some((apartamento) => apartamento.id === this.apartamentoId)) {
          this.apartamentoId = this.apartamentos[0]?.id ?? 0;
        }
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo consultar la disponibilidad.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  cargarMisReservas(): void {
    this.servicio.misReservas().subscribe({
      next: (respuesta) => {
        this.misReservas = respuesta.items;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar sus reservas.');
        this.cdr.markForCheck();
      },
    });
  }

  cargarPendientes(): void {
    this.cargando = true;
    this.servicio.pendientes().subscribe({
      next: (respuesta) => {
        this.pendientes = respuesta.items;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar las reservas pendientes.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  cargarReservasHoy(): void {
    this.cargando = true;
    this.servicio.reservasDeHoy().subscribe({
      next: (respuesta) => {
        this.reservasHoy = respuesta.items;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar las reservas de hoy.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  guardarReserva(): void {
    if (this.guardando) return;
    this.guardando = true;
    this.mensaje = '';
    this.error = false;
    this.servicio.crear({
      zona_id: this.zonaId,
      apartamento_id: this.apartamentoId,
      fecha: this.fecha,
      hora_inicio: this.horaInicio,
      hora_fin: this.horaFin,
      observaciones: this.observaciones,
    }).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.guardando = false;
        this.observaciones = '';
        this.cargarDisponibilidad();
        this.cargarMisReservas();
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo crear la reserva.');
        this.guardando = false;
        this.cdr.markForCheck();
      },
    });
  }

  gestionar(id: number, accion: 'aprobar' | 'rechazar' | 'cancelar'): void {
    const pregunta = accion === 'cancelar'
      ? '¿Desea cancelar esta reserva?'
      : `¿Desea ${accion} esta reserva?`;
    if (!window.confirm(pregunta)) return;

    this.servicio.actualizar(id, accion).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.error = false;
        if (accion === 'cancelar') {
          this.cargarMisReservas();
          this.cargarDisponibilidad();
        } else {
          this.cargarPendientes();
        }
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo actualizar la reserva.');
        this.cdr.markForCheck();
      },
    });
  }

  get reservasDeZona(): ReservaDia[] {
    return this.disponibilidad.filter((reserva) => reserva.zona_id === this.zonaId);
  }

  private fechaLocal(fecha: Date): string {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
  }

  private mostrarError(respuesta: HttpErrorResponse, alternativa: string): void {
    this.mensaje = respuesta.error?.message ?? alternativa;
    this.error = true;
  }
}
