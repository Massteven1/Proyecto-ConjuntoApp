import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth';
import { Apartamento, DatosApartamento, DatosTorre, InmueblesService, Torre } from '../../services/inmuebles';

@Component({
  selector: 'app-inmuebles',
  imports: [FormsModule],
  templateUrl: './inmuebles.html',
  styleUrl: './inmuebles.css',
})
export class Inmuebles implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly servicio = inject(InmueblesService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  torres: Torre[] = [];
  apartamentos: Apartamento[] = [];
  torre: DatosTorre = this.torreVacia();
  apartamento: DatosApartamento = this.apartamentoVacio();
  editandoTorreId: number | null = null;
  editandoApartamentoId: number | null = null;
  mensaje = '';
  error = false;
  cargando = true;

  ngOnInit(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        if (respuesta.user?.rol !== 'Administrador') {
          void this.router.navigateByUrl(respuesta.user ? '/inicio' : '/login');
          return;
        }
        this.cargar();
      },
      error: () => void this.router.navigateByUrl('/login'),
    });
  }

  cargar(): void {
    this.cargando = true;
    this.servicio.listar().subscribe({
      next: (respuesta) => {
        this.torres = respuesta.torres;
        this.apartamentos = respuesta.apartamentos;
        if (this.apartamento.torre_id === 0 && this.torres.length) this.apartamento.torre_id = this.torres[0].id;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar las torres y apartamentos.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  guardarTorre(): void {
    const solicitud = this.editandoTorreId === null
      ? this.servicio.crear('torre', this.torre)
      : this.servicio.actualizar('torre', this.editandoTorreId, this.torre);
    solicitud.subscribe({
      next: (respuesta) => {
        this.mostrarMensaje(respuesta.message ?? 'Cambios guardados.');
        this.torre = this.torreVacia();
        this.editandoTorreId = null;
        this.cargar();
      },
      error: (respuesta: HttpErrorResponse) => this.mostrarError(respuesta, 'No se pudo guardar la torre.'),
    });
  }

  guardarApartamento(): void {
    const solicitud = this.editandoApartamentoId === null
      ? this.servicio.crear('apartamento', this.apartamento)
      : this.servicio.actualizar('apartamento', this.editandoApartamentoId, this.apartamento);
    solicitud.subscribe({
      next: (respuesta) => {
        this.mostrarMensaje(respuesta.message ?? 'Cambios guardados.');
        this.apartamento = { ...this.apartamentoVacio(), torre_id: this.torres[0]?.id ?? 0 };
        this.editandoApartamentoId = null;
        this.cargar();
      },
      error: (respuesta: HttpErrorResponse) => this.mostrarError(respuesta, 'No se pudo guardar el apartamento.'),
    });
  }

  editarTorre(torre: Torre): void {
    this.editandoTorreId = torre.id;
    this.torre = { nombre: torre.nombre, pisos: Number(torre.pisos) };
  }

  editarApartamento(apartamento: Apartamento): void {
    this.editandoApartamentoId = apartamento.id;
    this.apartamento = {
      torre_id: Number(apartamento.torre_id),
      numero: apartamento.numero,
      piso: Number(apartamento.piso),
      area_m2: Number(apartamento.area_m2),
      coeficiente: Number(apartamento.coeficiente),
    };
  }

  eliminar(tipo: 'torre' | 'apartamento', item: Torre | Apartamento): void {
    const nombre = 'nombre' in item ? item.nombre : `apartamento ${item.numero}`;
    if (!window.confirm(`¿Deseas eliminar ${nombre}?`)) return;
    this.servicio.eliminar(tipo, item.id).subscribe({
      next: (respuesta) => { this.mostrarMensaje(respuesta.message ?? 'Registro eliminado.'); this.cargar(); },
      error: (respuesta: HttpErrorResponse) => this.mostrarError(respuesta, 'No se pudo eliminar el registro.'),
    });
  }

  cancelarTorre(): void { this.editandoTorreId = null; this.torre = this.torreVacia(); }
  cancelarApartamento(): void {
    this.editandoApartamentoId = null;
    this.apartamento = { ...this.apartamentoVacio(), torre_id: this.torres[0]?.id ?? 0 };
  }

  private mostrarMensaje(mensaje: string): void { this.mensaje = mensaje; this.error = false; this.cdr.markForCheck(); }
  private mostrarError(respuesta: HttpErrorResponse, alternativa: string): void {
    this.mensaje = respuesta.error?.message ?? alternativa;
    this.error = true;
    this.cdr.markForCheck();
  }
  private torreVacia(): DatosTorre { return { nombre: '', pisos: 1 }; }
  private apartamentoVacio(): DatosApartamento { return { torre_id: 0, numero: '', piso: 1, area_m2: 1, coeficiente: 0.0001 }; }
}
