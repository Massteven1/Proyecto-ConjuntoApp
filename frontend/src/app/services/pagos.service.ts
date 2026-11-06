import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface PagoCuota {
  id: number;
  apartamento_id: number;
  torre: string;
  apartamento: string;
  periodo: string;
  saldo: string;
  fecha_vencimiento: string;
}

@Injectable({ providedIn: 'root' })
export class PagosService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = '/api/pagos.php';

  cuotasPendientes(): Observable<PagoCuota[]> {
    return this.http.get<PagoCuota[]>(this.endpoint, { withCredentials: true });
  }

  registrar(data: { apartamento_id: number; fecha_pago: string; valor: number; medio: string; soporte: string }): Observable<{ message: string; pago_id: number; cuota_id: number }> {
    return this.http.post<{ message: string; pago_id: number; cuota_id: number }>(this.endpoint, data, { withCredentials: true });
  }
}
