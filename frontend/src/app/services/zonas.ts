import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface ZonaComun {
  id: number;
  nombre: string;
  aforo: number;
  hora_apertura: string;
  hora_cierre: string;
  costo: string;
  requiere_deposito: number;
}

export interface PaginaZonas {
  items: ZonaComun[];
  pagina: number;
  porPagina: number;
  total: number;
}

export interface DatosZona {
  nombre: string;
  aforo: number;
  hora_apertura: string;
  hora_cierre: string;
  costo: number;
  requiere_deposito: boolean;
}

interface RespuestaZona {
  message: string;
  id?: number;
}

@Injectable({ providedIn: 'root' })
export class ZonasService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = 'http://localhost:8000/controllers/zonas.php';

  listar(pagina: number): Observable<PaginaZonas> {
    return this.http.get<PaginaZonas>(`${this.endpoint}?pagina=${pagina}`, { withCredentials: true });
  }

  crear(datos: DatosZona): Observable<RespuestaZona> {
    return this.http.post<RespuestaZona>(this.endpoint, datos, { withCredentials: true });
  }

  actualizar(id: number, datos: DatosZona): Observable<RespuestaZona> {
    return this.http.put<RespuestaZona>(`${this.endpoint}?id=${id}`, datos, { withCredentials: true });
  }

  eliminar(id: number): Observable<RespuestaZona> {
    return this.http.delete<RespuestaZona>(`${this.endpoint}?id=${id}`, { withCredentials: true });
  }
}
