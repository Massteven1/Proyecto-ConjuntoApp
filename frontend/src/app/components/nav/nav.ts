import { ChangeDetectorRef, Component, OnDestroy, OnInit, inject } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { Subscription, filter } from 'rxjs';

import { AuthService, AuthUser } from '../../services/auth';

@Component({
  imports: [RouterLink, RouterLinkActive],
  host: { '[class.nav-visible]': 'visible' },
  selector: 'app-nav',
  styleUrl: './nav.css',
  templateUrl: './nav.html',
})
export class Nav implements OnInit, OnDestroy {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly cdr = inject(ChangeDetectorRef);
  private suscripcion?: Subscription;

  usuario: AuthUser | null = null;
  visible = false;

  get esAdministrador(): boolean {
    return this.usuario?.rol === 'Administrador';
  }

  get esResidente(): boolean {
    return this.usuario?.rol === 'Residente';
  }

  get esPortero(): boolean {
    return this.usuario?.rol === 'Portero';
  }

  get pagosActivo(): boolean {
    return ['/cuotas', '/pagos', '/estado-cuenta'].includes(this.router.url.split('?')[0]);
  }

  get gestionActiva(): boolean {
    return ['/tablero', '/usuarios', '/inmuebles', '/residencias', '/zonas'].includes(this.router.url.split('?')[0]);
  }

  get comunidadActiva(): boolean {
    return ['/visitantes', '/porteria', '/reservas', '/comunicados'].includes(this.router.url.split('?')[0]);
  }

  ngOnInit(): void {
    this.suscripcion = this.router.events
      .pipe(filter((evento) => evento instanceof NavigationEnd))
      .subscribe(() => this.cargarSesion());

    this.cargarSesion();
  }

  ngOnDestroy(): void {
    this.suscripcion?.unsubscribe();
  }

  cerrarSesion(): void {
    this.auth.logout().subscribe({
      next: () => void this.router.navigateByUrl('/login'),
      error: (respuesta: HttpErrorResponse) => {
        console.error(respuesta.error?.message ?? 'No se pudo cerrar la sesión.');
        void this.router.navigateByUrl('/login');
      },
    });
  }

  private cargarSesion(): void {
    if (this.esPantallaDeAcceso()) {
      this.usuario = null;
      this.visible = false;
      this.cdr.markForCheck();
      return;
    }

    this.auth.currentUser().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.user ?? null;
        this.visible = this.usuario !== null && !this.esPantallaDeAcceso();
        this.cdr.markForCheck();
      },
      error: () => {
        this.usuario = null;
        this.visible = false;
        this.cdr.markForCheck();
      },
    });
  }

  private esPantallaDeAcceso(): boolean {
    return this.router.url.startsWith('/login') || this.router.url.startsWith('/registro');
  }
}
