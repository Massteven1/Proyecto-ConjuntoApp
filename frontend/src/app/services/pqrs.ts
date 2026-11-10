import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface PqrsItem {
  id: number;
  radicado: string;
  tipo: string;
  categoria_id: number;
  categoria: string;
  usuario_id: number;
  usuario: string;
  apartamento_id: number;
  apartamento: string;
  torre: string;
  asunto: string;
  descripcion: string;
  adjunto: string | null;
  estado: 'Radicada' | 'En trámite' | 'Respondida' | 'Cerrada';
  fecha_limite: string;
  calificacion: number | null;
  respuesta: string | null;
  fecha_respuesta: string | null;
  vencida: boolean;
}

export interface PqrsResumen {
  totales: {
    radicadas: number;
    en_tramite: number;
    respondidas: number;
    cerradas: number;
    total: number;
    promedio_dias_respuesta: number | null;
  };
  categorias: Array<{ id: number; nombre: string; total: number }>;
}

export interface PqrsListResponse {
  items: PqrsItem[];
  resumen: PqrsResumen | null;
  categorias: PqrsCategoria[];
  apartamentos: PqrsApartamento[];
}

export interface PqrsCategoria {
  id: number;
  nombre: string;
}

export interface PqrsApartamento {
  id: number;
  numero: string;
  torre: string;
}

export interface PqrsHistorialItem {
  estado_anterior: string | null;
  estado_nuevo: string;
  comentario: string;
  fecha: string;
  usuario: string;
}

export interface CrearPqrsPayload {
  tipo: string;
  categoria_id: number;
  apartamento_id: number;
  asunto: string;
  descripcion: string;
}

@Injectable({ providedIn: 'root' })
export class PqrsService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/pqrs.php';

  // Consulto la lista con los filtros seleccionados en la pantalla.
  listar(estado = '', categoriaId = 0): Observable<PqrsListResponse> {
    let params = new HttpParams();
    if (estado) params = params.set('estado', estado);
    if (categoriaId > 0) params = params.set('categoria_id', categoriaId);
    return this.http.get<PqrsListResponse>(this.endpoint, { params, withCredentials: true });
  }

  // Envío los datos como formulario para incluir el archivo opcional.
  crear(data: CrearPqrsPayload, archivo: File | null): Observable<{ message: string; id: number }> {
    const formData = new FormData();
    formData.append('tipo', data.tipo);
    formData.append('categoria_id', String(data.categoria_id));
    formData.append('apartamento_id', String(data.apartamento_id));
    formData.append('asunto', data.asunto);
    formData.append('descripcion', data.descripcion);
    if (archivo) formData.append('adjunto', archivo);
    return this.http.post<{ message: string; id: number }>(this.endpoint, formData, { withCredentials: true });
  }

  // Solicito al administrador guardar el estado y su comentario de seguimiento.
  cambiarEstado(id: number, estado: string, respuesta = ''): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.endpoint}?action=estado&id=${id}`, { estado, respuesta }, { withCredentials: true });
  }

  // Envío la calificación que asigna el residente a la respuesta.
  calificar(id: number, calificacion: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.endpoint}?action=calificar&id=${id}`, { calificacion }, { withCredentials: true });
  }

  // Consulto los cambios de estado de una PQRS.
  historial(id: number): Observable<{ items: PqrsHistorialItem[] }> {
    return this.http.get<{ items: PqrsHistorialItem[] }>(`${this.endpoint}?action=historial&id=${id}`, { withCredentials: true });
  }
}
