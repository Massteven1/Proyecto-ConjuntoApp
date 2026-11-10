import { ChangeDetectorRef, Component, OnInit, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService, AuthUser } from '../../services/auth';

@Component({
  selector: 'app-inicio',
  imports: [RouterLink],
  templateUrl: './inicio.html',
  styleUrl: './inicio.css',
})
export class Inicio implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly changeDetector = inject(ChangeDetectorRef);
  usuario: AuthUser | null = null;
  cargando = true;
  mensaje = '';

  ngOnInit(): void {
    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        this.cargando = false;
        this.changeDetector.markForCheck();
        if (!this.usuario) void this.router.navigateByUrl('/login');
      },
      error: () => {
        this.cargando = false;
        this.changeDetector.markForCheck();
        void this.router.navigateByUrl('/login');
      },
    });
  }

  cerrarSesion(): void {
    this.cargando = true;
    this.auth.logout().subscribe({
      next: () => {
        void this.router.navigateByUrl('/login');
        this.changeDetector.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mensaje = respuesta.error?.message ?? 'No se pudo cerrar la sesión.';
        this.cargando = false;
        this.changeDetector.markForCheck();
      },
    });
  }
}
