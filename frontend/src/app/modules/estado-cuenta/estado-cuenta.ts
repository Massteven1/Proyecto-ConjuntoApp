import { Component, inject, signal } from '@angular/core';
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

  constructor() {
    this.service.listar().subscribe({
      next: (data) => this.cuentas.set(data),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible consultar el estado de cuenta.')
    });
  }
}
