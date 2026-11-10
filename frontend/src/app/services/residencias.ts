import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface Residencia {
  id: number;
  usuario_id: number;
  apartamento_id: number;
  tipo: 'propietario' | 'arrendatario';
  fecha_inicio: string;
  fecha_fin: string | null;
  nombres: string;
  apellidos: string;
  apartamento: string;
  torre: string;
}

export interface ResidenteDisponible { id: number; nombres: string; apellidos: string; }
export interface ApartamentoDisponible { id: number; numero: string; torre: string; }
export interface DatosResidencia {
  usuario_id: number;
  apartamento_id: number;
  tipo: 'propietario' | 'arrendatario';
  fecha_inicio: string;
  fecha_fin: string;
}
interface RespuestaResidencias {
  residencias: Residencia[];
  residentes: ResidenteDisponible[];
  apartamentos: ApartamentoDisponible[];
  message?: string;
}

@Injectable({ providedIn: 'root' })
export class ResidenciasService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/residencias.php';

  listar(): Observable<RespuestaResidencias> {
    return this.http.get<RespuestaResidencias>(this.endpoint, { withCredentials: true });
  }
  crear(datos: DatosResidencia): Observable<RespuestaResidencias> {
    return this.http.post<RespuestaResidencias>(this.endpoint, datos, { withCredentials: true });
  }
  actualizar(id: number, datos: DatosResidencia): Observable<RespuestaResidencias> {
    return this.http.put<RespuestaResidencias>(`${this.endpoint}?id=${id}`, datos, { withCredentials: true });
  }
}
