import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { ResumenTablero, TableroService } from '../../services/tablero';

@Component({
  selector: 'app-tablero',
  templateUrl: './tablero.html',
  styleUrl: './tablero.css',
})
export class Tablero {
  private readonly service = inject(TableroService);
  public resumen = signal<ResumenTablero | null>(null);
  public error = signal('');

  constructor() {
    this.service.consultar().subscribe({
      next: (data) => this.resumen.set(data),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible cargar el tablero.')
    });
  }
}
