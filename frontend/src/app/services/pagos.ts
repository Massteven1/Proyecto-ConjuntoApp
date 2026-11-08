import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface CuotaPendiente {
  cuota_id: number;
  apartamento_id: number;
  torre: string;
  apartamento: string;
  periodo: string;
  saldo: string;
  fecha_vencimiento: string;
}

export interface RegistrarPago {
  apartamento_id: number;
  fecha_pago: string;
  valor: number;
  medio: string;
  soporte: string;
}

export interface PagoRegistrado {
  message: string;
  pago_id: number;
  cuota_id: number;
}

@Injectable({ providedIn: 'root' })
export class PagosService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/pagos.php';

  cuotasPendientes(): Observable<CuotaPendiente[]> {
    return this.http.get<CuotaPendiente[]>(this.endpoint, { withCredentials: true });
  }

  registrar(data: RegistrarPago): Observable<PagoRegistrado> {
    return this.http.post<PagoRegistrado>(this.endpoint, data, { withCredentials: true });
  }
}
