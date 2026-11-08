import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService, AuthUser } from '../../services/auth';
import { Comunicado, ComunicadosService } from '../../services/comunicados';

@Component({
  selector: 'app-comunicados',
  imports: [FormsModule],
  templateUrl: './comunicados.html',
  styleUrl: './comunicados.css',
})
export class Comunicados {
  private readonly service = inject(ComunicadosService);
  private readonly auth = inject(AuthService);
  public usuario = signal<AuthUser | null>(null);
  public comunicados = signal<Comunicado[]>([]);
  public titulo = '';
  public contenido = '';
  public vigente_hasta = '';
  public error = signal('');
  public mensaje = signal('');

  constructor() {
    this.auth.currentUser().subscribe({
      next: (session) => this.usuario.set(session.user ?? null),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.message ?? 'Debe iniciar sesión.')
    });
    this.cargar();
  }

  esAdministrador(): boolean {
    return this.usuario()?.rol.toLowerCase() === 'administrador';
  }

  cargar(): void {
    this.service.listar().subscribe({
      next: (data) => this.comunicados.set(data),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible cargar los comunicados.')
    });
  }

  publicar(): void {
    this.error.set('');
    this.service.publicar({ titulo: this.titulo, contenido: this.contenido, vigente_hasta: this.vigente_hasta }).subscribe({
      next: () => { this.mensaje.set('Comunicado publicado.'); this.titulo = ''; this.contenido = ''; this.cargar(); },
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible publicar el comunicado.')
    });
  }

  marcarLeido(comunicado: Comunicado): void {
    this.service.marcarLeido(comunicado.id).subscribe({
      next: () => this.comunicados.update((items) => items.map((item) => item.id === comunicado.id ? { ...item, leido: true } : item)),
      error: (err: HttpErrorResponse) => this.error.set(err.error?.error ?? 'No fue posible marcar el comunicado.')
    });
  }
}
