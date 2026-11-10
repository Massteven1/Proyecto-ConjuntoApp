import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AuthService, AuthUser } from '../../services/auth';
import {
  PqrsApartamento,
  PqrsCategoria,
  PqrsHistorialItem,
  PqrsItem,
  PqrsResumen,
  PqrsService,
} from '../../services/pqrs';

@Component({
  selector: 'app-pqrs',
  imports: [CommonModule, FormsModule],
  templateUrl: './pqrs.html',
  styleUrl: './pqrs.css',
})
export class Pqrs {
  private readonly service = inject(PqrsService);
  private readonly auth = inject(AuthService);

  usuario = signal<AuthUser | null>(null);
  items = signal<PqrsItem[]>([]);
  resumen = signal<PqrsResumen | null>(null);
  categorias = signal<PqrsCategoria[]>([]);
  apartamentos = signal<PqrsApartamento[]>([]);
  mensaje = signal('');
  error = signal('');

  tipo = 'Petición';
  categoriaId = 0;
  apartamentoId = 0;
  asunto = '';
  descripcion = '';
  archivoAdjunto: File | null = null;
  private inputAdjunto?: HTMLInputElement;
  estadoFiltro = '';
  categoriaFiltro = 0;
  adminEstado: Record<number, string> = {};
  adminRespuesta: Record<number, string> = {};
  historialVisible = signal<Record<number, boolean>>({});
  historiales = signal<Record<number, PqrsHistorialItem[]>>({});

  // Consulto la sesión antes de cargar las PQRS disponibles para el usuario.
  constructor() {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario.set(respuesta.user ?? null);
        this.cargar();
      },
      error: () => this.error.set('Debe iniciar sesión para acceder a PQRS.'),
    });
  }

  get esAdministrador(): boolean {
    return this.usuario()?.rol?.toLowerCase() === 'administrador';
  }

  get esResidente(): boolean {
    return this.usuario()?.rol?.toLowerCase() === 'residente';
  }

  // Cargo la lista, las opciones del formulario y el resumen del reporte.
  cargar(mensaje = ''): void {
    this.error.set('');
    this.mensaje.set(mensaje);
    this.service.listar(this.estadoFiltro, this.categoriaFiltro).subscribe({
      next: (respuesta) => {
        this.items.set(respuesta.items ?? []);
        this.resumen.set(respuesta.resumen ?? null);
        this.categorias.set(respuesta.categorias ?? []);
        this.apartamentos.set(respuesta.apartamentos ?? []);
        if (this.categoriaId === 0 && this.categorias().length > 0) {
          this.categoriaId = this.categorias()[0].id;
        }
        if (this.apartamentoId === 0 && this.apartamentos().length > 0) {
          this.apartamentoId = this.apartamentos()[0].id;
        }
      },
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible cargar las PQRS.'),
    });
  }

  // Envío una PQRS con los datos validados y el adjunto opcional.
  crear(): void {
    if (!this.esResidente) {
      this.error.set('Solo los residentes pueden radicar una PQRS.');
      return;
    }
    if (!this.categoriaId || !this.apartamentoId || !this.asunto.trim() || !this.descripcion.trim()) {
      this.error.set('Completa los datos obligatorios antes de radicar.');
      return;
    }
    if (this.archivoAdjunto && this.archivoAdjunto.size > 5 * 1024 * 1024) {
      this.error.set('El adjunto no puede superar los 5 MB.');
      return;
    }

    this.error.set('');
    this.service.crear({
      tipo: this.tipo,
      categoria_id: this.categoriaId,
      apartamento_id: this.apartamentoId,
      asunto: this.asunto,
      descripcion: this.descripcion,
    }, this.archivoAdjunto).subscribe({
      next: () => {
        this.asunto = '';
        this.descripcion = '';
        this.archivoAdjunto = null;
        if (this.inputAdjunto) this.inputAdjunto.value = '';
        this.cargar('PQRS creada correctamente.');
      },
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible crear la PQRS.'),
    });
  }

  seleccionarAdjunto(evento: Event): void {
    const input = evento.target as HTMLInputElement;
    this.inputAdjunto = input;
    this.archivoAdjunto = input.files?.[0] ?? null;
  }

  // Muestro el seguimiento completo en orden cronológico.
  verHistorial(item: PqrsItem): void {
    const visible = !this.historialVisible()[item.id];
    this.historialVisible.update((items) => ({ ...items, [item.id]: visible }));
    if (!visible || this.historiales()[item.id]) return;

    this.service.historial(item.id).subscribe({
      next: (respuesta) => this.historiales.update((items) => ({ ...items, [item.id]: respuesta.items })),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible consultar el seguimiento.'),
    });
  }

  // Guardo el nuevo estado y el comentario del administrador.
  actualizarEstado(item: PqrsItem): void {
    if (!this.esAdministrador) {
      this.error.set('Solo el administrador puede cambiar el estado.');
      return;
    }

    const estado = this.adminEstado[item.id] ?? item.estado;
    const respuesta = this.adminRespuesta[item.id] ?? '';

    this.service.cambiarEstado(item.id, estado, respuesta).subscribe({
      next: () => this.cargar('Estado actualizado.'),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible actualizar la PQRS.'),
    });
  }

  // Envío la calificación una sola vez; la API confirma que la PQRS me pertenece.
  calificar(item: PqrsItem, valor: number): void {
    if (!this.esResidente) {
      this.error.set('Solo el residente puede calificar la respuesta.');
      return;
    }

    this.service.calificar(item.id, valor).subscribe({
      next: () => this.cargar('Gracias por calificar la respuesta.'),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible calificar la respuesta.'),
    });
  }
}
