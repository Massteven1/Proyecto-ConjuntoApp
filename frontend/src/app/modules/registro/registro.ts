import { ChangeDetectorRef, Component, inject } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../services/auth';

@Component({
  selector: 'app-registro',
  imports: [FormsModule, RouterLink],
  templateUrl: './registro.html',
  styleUrl: './registro.css',
})
export class Registro {
  private readonly auth = inject(AuthService);
  private readonly changeDetector = inject(ChangeDetectorRef);
  nombres = '';
  apellidos = '';
  documento = '';
  correo = '';
  telefono = '';
  password = '';
  mensaje = '';
  error = false;
  cargando = false;

  onSubmit(): void {
    this.cargando = true;
    this.mensaje = '';
    this.error = false;
    this.auth.register({
      nombres: this.nombres,
      apellidos: this.apellidos,
      documento: this.documento,
      correo: this.correo,
      telefono: this.telefono,
      password: this.password,
    }).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.message;
        this.password = '';
        this.cargando = false;
        this.changeDetector.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mensaje = respuesta.error?.message ?? 'No se pudo conectar con el servidor.';
        this.error = true;
        this.cargando = false;
        this.changeDetector.markForCheck();
      },
    });
  }
}
