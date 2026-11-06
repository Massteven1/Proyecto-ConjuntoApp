import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { PagosService, PagoCuota } from '../../services/pagos.service';

@Component({
  selector: 'app-pagos',
  imports: [FormsModule],
  templateUrl: './pagos.html',
  styleUrl: './pagos.css',
})
export class Pagos {
  private readonly service = inject(PagosService);
  public cuotas = signal<PagoCuota[]>([]);
  public apartamento_id: number | null = null;
  public fecha_pago = new Date().toISOString().slice(0, 10);
  public valor: number | null = null;
  public medio = 'Transferencia';
  public soporte = '';
  public error = signal('');
  public mensaje = signal('');

  constructor() {
    this.service.cuotasPendientes().subscribe({
      next: (data) => this.cuotas.set(data),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible consultar las cuotas.')
    });
  }

  registrar(): void {
    if (!this.apartamento_id || !this.fecha_pago || !this.valor || this.valor <= 0 || !this.medio.trim()) {
      this.error.set('Complete los campos con valores válidos.');
      return;
    }
    this.error.set('');
    this.mensaje.set('');
    this.service.registrar({
      apartamento_id: this.apartamento_id,
      fecha_pago: this.fecha_pago,
      valor: this.valor,
      medio: this.medio,
      soporte: this.soporte
    }).subscribe({
      next: (result) => {
        this.mensaje.set(result.message);
        this.valor = null;
        this.soporte = '';
        this.service.cuotasPendientes().subscribe({ next: (data) => this.cuotas.set(data) });
      },
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible registrar el pago.')
    });
  }
}
