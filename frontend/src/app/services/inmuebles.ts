import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface Torre {
  id: number;
  nombre: string;
  pisos: number;
  apartamentos: number;
}

export interface Apartamento {
  id: number;
  torre_id: number;
  torre: string;
  numero: string;
  piso: number;
  area_m2: number;
  coeficiente: number;
}

export interface DatosTorre {
  nombre: string;
  pisos: number;
}

export interface DatosApartamento {
  torre_id: number;
  numero: string;
  piso: number;
  area_m2: number;
  coeficiente: number;
}

interface RespuestaInmuebles {
  torres: Torre[];
  apartamentos: Apartamento[];
  message?: string;
}

@Injectable({ providedIn: 'root' })
export class InmueblesService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/inmuebles.php';

  listar(): Observable<RespuestaInmuebles> {
    return this.http.get<RespuestaInmuebles>(`${this.endpoint}?tipo=torre`, { withCredentials: true });
  }

  crear(tipo: 'torre' | 'apartamento', datos: DatosTorre | DatosApartamento): Observable<RespuestaInmuebles> {
    return this.http.post<RespuestaInmuebles>(`${this.endpoint}?tipo=${tipo}`, datos, { withCredentials: true });
  }

  actualizar(tipo: 'torre' | 'apartamento', id: number, datos: DatosTorre | DatosApartamento): Observable<RespuestaInmuebles> {
    return this.http.put<RespuestaInmuebles>(`${this.endpoint}?tipo=${tipo}&id=${id}`, datos, { withCredentials: true });
  }

  eliminar(tipo: 'torre' | 'apartamento', id: number): Observable<RespuestaInmuebles> {
    return this.http.delete<RespuestaInmuebles>(`${this.endpoint}?tipo=${tipo}&id=${id}`, { withCredentials: true });
  }
}
