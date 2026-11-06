import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface Comunicado {
  id: number;
  titulo: string;
  contenido: string;
  fecha_publicacion: string;
  vigente_hasta: string;
  leido: boolean;
}

@Injectable({ providedIn: 'root' })
export class ComunicadosService {
  private readonly http = inject(HttpClient);

  listar(): Observable<Comunicado[]> {
    return this.http.get<Comunicado[]>('/api/comunicados.php', { withCredentials: true });
  }

  publicar(data: { titulo: string; contenido: string; vigente_hasta: string }): Observable<{ message: string; id: number }> {
    return this.http.post<{ message: string; id: number }>('/api/comunicados.php', data, { withCredentials: true });
  }

  marcarLeido(id: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`/api/comunicados.php?id=${id}`, {}, { withCredentials: true });
  }
}
