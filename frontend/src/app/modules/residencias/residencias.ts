import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth';
import { DatosResidencia, Residencia, ResidenciasService } from '../../services/residencias';

@Component({
  selector: 'app-residencias',
  imports: [FormsModule],
  templateUrl: './residencias.html',
  styleUrl: './residencias.css',
})
export class Residencias implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly servicio = inject(ResidenciasService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  residencias: Residencia[] = [];
  residentes: { id: number; nombres: string; apellidos: string }[] = [];
  apartamentos: { id: number; numero: string; torre: string }[] = [];
  formulario: DatosResidencia = this.formularioVacio();
  editandoId: number | null = null;
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
        this.residencias = respuesta.residencias;
        this.residentes = respuesta.residentes;
        this.apartamentos = respuesta.apartamentos;
        if (this.formulario.usuario_id === 0) this.formulario.usuario_id = this.residentes[0]?.id ?? 0;
        if (this.formulario.apartamento_id === 0) this.formulario.apartamento_id = this.apartamentos[0]?.id ?? 0;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar las asociaciones.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  guardar(): void {
    const solicitud = this.editandoId === null
      ? this.servicio.crear(this.formulario)
      : this.servicio.actualizar(this.editandoId, this.formulario);
    solicitud.subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message ?? 'Asociación guardada.';
        this.error = false;
        this.editandoId = null;
        this.formulario = this.formularioVacio();
        this.cargar();
      },
      error: (respuesta: HttpErrorResponse) => this.mostrarError(respuesta, 'No se pudo guardar la asociación.'),
    });
  }

  editar(residencia: Residencia): void {
    this.editandoId = residencia.id;
    this.formulario = {
      usuario_id: Number(residencia.usuario_id),
      apartamento_id: Number(residencia.apartamento_id),
      tipo: residencia.tipo,
      fecha_inicio: residencia.fecha_inicio,
      fecha_fin: residencia.fecha_fin ?? '',
    };
  }

  cancelarEdicion(): void {
    this.editandoId = null;
    this.formulario = this.formularioVacio();
  }

  vigente(residencia: Residencia): boolean {
    const hoy = new Date();
    const fechaHoy = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;
    return !residencia.fecha_fin || residencia.fecha_fin >= fechaHoy;
  }

  private mostrarError(respuesta: HttpErrorResponse, alternativa: string): void {
    this.mensaje = respuesta.error?.message ?? alternativa;
    this.error = true;
    this.cdr.markForCheck();
  }
  private formularioVacio(): DatosResidencia {
    return { usuario_id: 0, apartamento_id: 0, tipo: 'propietario', fecha_inicio: '', fecha_fin: '' };
  }
}
