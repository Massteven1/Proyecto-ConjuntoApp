import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface CuotaGenerada {
  id: number;
  apartamento_id: number;
  torre: string;
  apartamento: string;
  periodo: string;
  valor: string;
  fecha_vencimiento: string;
  saldo: string;
  estado: string;
  residentes: { id: number; nombre: string; tipo: string }[];
}

export interface RespuestaGeneracionCuotas {
  periodo: string;
  generadas: number;
  cuotas: CuotaGenerada[];
}

@Injectable({
  providedIn: 'root'
})
export class cuotasService {
    private http = inject(HttpClient);
    private url = 'http://localhost:8000/api/cuotasAPI.php';

    public generarCuotas(periodo: string, presupuesto_base: number, fecha_vencimiento: string): Observable<RespuestaGeneracionCuotas> {
        return this.http.post<RespuestaGeneracionCuotas>(this.url, {
            periodo,
            presupuesto_base,
            fecha_vencimiento
        });
    }
}
