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
export interface PublicarComunicado {
  titulo: string;
  contenido: string;
  vigente_hasta: string;
}

@Injectable({ providedIn: 'root' })
export class ComunicadosService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/comunicados.php';

  listar(): Observable<Comunicado[]> {
    return this.http.get<Comunicado[]>(this.endpoint, { withCredentials: true });
  }

  publicar(data: PublicarComunicado): Observable<{ message: string; id: number }> {
    return this.http.post<{ message: string; id: number }>(this.endpoint, data, { withCredentials: true });
  }

  marcarLeido(id: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.endpoint}?id=${id}`, {}, { withCredentials: true });
  }
}
