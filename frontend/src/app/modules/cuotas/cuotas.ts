import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { cuotasService, RespuestaGeneracionCuotas } from '../../services/cuotasService';

@Component({
  selector: 'app-cuotas',
  imports: [FormsModule],
  templateUrl: './cuotas.html',
  styleUrl: './cuotas.css',
})
export class Cuotas {
  public cuotaService = inject(cuotasService);
  public periodo = new Date().toISOString().slice(0, 7);
  public presupuestoBase: number | null = null;
  public fechaVencimiento = '';
  public respuesta = signal<RespuestaGeneracionCuotas | null>(null);
  public error = signal('');
  public cargando = signal(false);

  generarCuotas(): void {
    if (this.presupuestoBase === null || !this.periodo || !this.fechaVencimiento) {
      this.error.set('Complete todos los campos con valores válidos.');
      return;
    }
    this.error.set('');
    this.respuesta.set(null);
    this.cargando.set(true);
    this.cuotaService.generarCuotas(this.periodo, this.presupuestoBase, this.fechaVencimiento).subscribe({
      next: (response) => {
        this.respuesta.set(response);
        this.cargando.set(false);
      },
      error: (err: HttpErrorResponse) => {
        this.error.set(err.error?.error ?? 'No fue posible generar las cuotas. Verifique la conexión con el servidor.');
        this.cargando.set(false);
      }
    });
  }
}
