import { Component, computed, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { EstadoCuenta, EstadoCuentaService } from '../../services/estado-cuenta';

@Component({
  selector: 'app-estado-cuenta',
  templateUrl: './estado-cuenta.html',
  styleUrl: './estado-cuenta.css',
})
export class EstadoCuentaComponent {
  private readonly service = inject(EstadoCuentaService);
  public cuentas = signal<EstadoCuenta[]>([]);
  public error = signal('');

  public totalFacturado = computed(() =>
    this.cuentas().reduce(
      (suma, cuenta) => suma + this.numero(cuenta.saldo_total) + this.numero(cuenta.valor_pagado_total),
      0,
    ),
  );

  public totalPagado = computed(() =>
    this.cuentas().reduce((suma, cuenta) => suma + this.numero(cuenta.valor_pagado_total), 0),
  );

  public saldoPendiente = computed(() =>
    this.cuentas().reduce((suma, cuenta) => suma + this.numero(cuenta.saldo_total), 0),
  );

  public cuotasEnMora = computed(() =>
    this.cuentas().reduce((suma, cuenta) => suma + this.numero(cuenta.cuotas_en_mora), 0),
  );

  constructor() {
    this.service.listar().subscribe({
      next: (data) => this.cuentas.set(data),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible consultar el estado de cuenta.')
    });
  }

  private numero(valor: string | number | null | undefined): number {
    const numero = Number(valor);
    return Number.isFinite(numero) ? numero : 0;
  }
}
