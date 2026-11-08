import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export interface EstadoCuenta {
  apartamento_id: number;
  torre: string;
  apartamento: string;
  cuotas_pendientes: number;
  cuotas_en_mora: number;
  saldo_total: string;
  valor_pagado_total: string;
  residentes: string | null;
}

@Injectable({ providedIn: 'root' })
export class EstadoCuentaService {
  private readonly http = inject(HttpClient);

  listar(): Observable<EstadoCuenta[]> {
    return this.http.get<EstadoCuenta[]>('/api/estadoCuenta.php', { withCredentials: true });
  }
}
