import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface ZonaReserva {
  id: number;
  nombre: string;
  aforo: number;
  hora_apertura: string;
  hora_cierre: string;
  costo: string;
  requiere_deposito: number;
}

export interface ApartamentoReserva {
  id: number;
  torre: string;
  apartamento: string;
}

export interface ReservaDia {
  id: number;
  zona_id: number;
  zona_nombre: string;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: string;
}

export interface ReservaPropia {
  id: number;
  zona_nombre: string;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: string;
  apartamento: string;
  torre: string;
}

export interface ReservaPendiente extends ReservaDia {
  observaciones: string | null;
  apartamento: string;
  torre: string;
  residente: string;
}

export interface ReservaPorteria {
  zona_nombre: string;
  hora_inicio: string;
  hora_fin: string;
  observaciones: string | null;
  apartamento: string;
  torre: string;
  residente: string;
}

export interface DisponibilidadReservas {
  zonas: ZonaReserva[];
  apartamentos: ApartamentoReserva[];
  reservas: ReservaDia[];
}

export interface RespuestaReservas<T> {
  items: T[];
  message?: string;
  id?: number;
}

export interface DatosReserva {
  zona_id: number;
  apartamento_id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  observaciones: string;
}

@Injectable({ providedIn: 'root' })
export class ReservasService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = 'http://localhost:8000/controllers/reservas.php';

  disponibilidad(fecha: string): Observable<DisponibilidadReservas> {
    return this.http.get<DisponibilidadReservas>(
      `${this.endpoint}?accion=disponibilidad&fecha=${fecha}`,
      { withCredentials: true },
    );
  }

  misReservas(): Observable<RespuestaReservas<ReservaPropia>> {
    return this.http.get<RespuestaReservas<ReservaPropia>>(
      `${this.endpoint}?accion=mis-reservas`,
      { withCredentials: true },
    );
  }

  pendientes(): Observable<RespuestaReservas<ReservaPendiente>> {
    return this.http.get<RespuestaReservas<ReservaPendiente>>(
      `${this.endpoint}?accion=pendientes`,
      { withCredentials: true },
    );
  }

  reservasDeHoy(): Observable<RespuestaReservas<ReservaPorteria>> {
    return this.http.get<RespuestaReservas<ReservaPorteria>>(
      `${this.endpoint}?accion=hoy`,
      { withCredentials: true },
    );
  }

  crear(datos: DatosReserva): Observable<{ message: string; id: number }> {
    return this.http.post<{ message: string; id: number }>(
      this.endpoint,
      datos,
      { withCredentials: true },
    );
  }

  actualizar(id: number, accion: 'aprobar' | 'rechazar' | 'cancelar'): Observable<{ message: string }> {
    return this.http.put<{ message: string }>(
      `${this.endpoint}?id=${id}`,
      { accion },
      { withCredentials: true },
    );
  }
}
