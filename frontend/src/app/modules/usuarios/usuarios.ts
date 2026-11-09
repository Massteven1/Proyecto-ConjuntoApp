import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth';
import { DatosUsuario, RolUsuario, UsuarioAdmin, UsuariosService } from '../../services/usuarios';

@Component({
  selector: 'app-usuarios',
  imports: [FormsModule],
  templateUrl: './usuarios.html',
  styleUrl: './usuarios.css',
})
export class Usuarios implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly servicio = inject(UsuariosService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  usuarios: UsuarioAdmin[] = [];
  roles: RolUsuario[] = [];
  formulario: DatosUsuario = this.formularioVacio();
  editandoId: number | null = null;
  mensaje = '';
  error = false;
  cargando = true;
  guardando = false;
  usuarioActualId = 0;

  ngOnInit(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        if (respuesta.user?.rol !== 'Administrador') {
          void this.router.navigateByUrl(respuesta.user ? '/inicio' : '/login');
          return;
        }
        this.usuarioActualId = respuesta.user.id;
        this.cargar();
      },
      error: () => void this.router.navigateByUrl('/login'),
    });
  }

  cargar(): void {
    this.cargando = true;
    this.servicio.listar().subscribe({
      next: (respuesta) => {
        this.usuarios = respuesta.usuarios;
        this.roles = respuesta.roles;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudieron cargar los usuarios.');
        this.cargando = false;
        this.cdr.markForCheck();
      },
    });
  }

  guardar(): void {
    if (this.guardando) return;
    this.guardando = true;
    this.mensaje = '';
    this.error = false;
    const solicitud = this.editandoId === null
      ? this.servicio.crear(this.formulario)
      : this.servicio.actualizar(this.editandoId, this.formulario);
    solicitud.subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.formulario = this.formularioVacio();
        this.editandoId = null;
        this.guardando = false;
        this.cargar();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo guardar el usuario.');
        this.guardando = false;
        this.cdr.markForCheck();
      },
    });
  }

  editar(usuario: UsuarioAdmin): void {
    this.editandoId = usuario.id;
    this.formulario = {
      rol_id: usuario.rol_id,
      nombres: usuario.nombres,
      apellidos: usuario.apellidos,
      documento: usuario.documento,
      correo: usuario.correo,
      telefono: usuario.telefono ?? '',
    };
    this.mensaje = '';
    this.error = false;
  }

  cancelarEdicion(): void {
    this.editandoId = null;
    this.formulario = this.formularioVacio();
  }

  cambiarEstado(usuario: UsuarioAdmin): void {
    const activo = Number(usuario.activo) !== 1;
    this.servicio.cambiarEstado(usuario.id, activo).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.error = false;
        this.cargar();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mostrarError(respuesta, 'No se pudo cambiar el estado del usuario.');
        this.cdr.markForCheck();
      },
    });
  }

  estaActivo(usuario: UsuarioAdmin): boolean {
    return Number(usuario.activo) === 1;
  }

  private mostrarError(respuesta: HttpErrorResponse, alternativa: string): void {
    this.mensaje = respuesta.error?.message ?? alternativa;
    this.error = true;
  }

  private formularioVacio(): DatosUsuario {
    return { rol_id: 0, nombres: '', apellidos: '', documento: '', correo: '', telefono: '', password: '' };
  }
}
