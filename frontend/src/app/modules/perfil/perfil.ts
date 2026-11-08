import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService, AuthUser } from '../../services/auth';

@Component({
  selector: 'app-perfil',
  imports: [FormsModule, RouterLink],
  templateUrl: './perfil.html',
  styleUrl: './perfil.css',
})
export class Perfil implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);

  usuario: AuthUser | null = null;
  actual = '';
  nueva = '';
  confirmar = '';
  mensaje = '';
  error = false;
  cargando = true;
  guardando = false;

  ngOnInit(): void {
    this.auth.profile().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        this.cargando = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.cargando = false;
        this.cdr.markForCheck();
        void this.router.navigateByUrl('/login');
      },
    });
  }

  cambiarContrasena(): void {
    this.mensaje = '';
    this.error = false;
    if (this.nueva !== this.confirmar) {
      this.mensaje = 'La nueva contraseña y su confirmación no coinciden.';
      this.error = true;
      return;
    }
    this.guardando = true;
    this.auth.changePassword(this.actual, this.nueva).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.actual = '';
        this.nueva = '';
        this.confirmar = '';
        this.guardando = false;
        this.cdr.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mensaje = respuesta.error?.message ?? 'No se pudo cambiar la contraseña.';
        this.error = true;
        this.guardando = false;
        this.cdr.markForCheck();
      },
    });
  }
}
