import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface ResumenTablero {
  porcentaje_cartera: number;
  pqrs_abiertas: number;
  reservas_semana: number;
  visitantes_hoy: number;
}

@Injectable({ providedIn: 'root' })
export class TableroService {
  private readonly http = inject(HttpClient);

  consultar(): Observable<ResumenTablero> {
    return this.http.get<ResumenTablero>('/api/tablero.php', { withCredentials: true });
  }
}
