import { ChangeDetectorRef, Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../services/auth';

@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly changeDetector = inject(ChangeDetectorRef);
  correo = '';
  password = '';
  mensaje = '';
  error = false;
  cargando = false;

  onSubmit(): void {
    this.cargando = true;
    this.mensaje = '';
    this.error = false;
    this.auth.login(this.correo, this.password).subscribe({
      next: (respuesta) => {
        this.cargando = false;
        void this.router.navigateByUrl('/inicio');
        this.changeDetector.markForCheck();
      },
      error: (respuesta: HttpErrorResponse) => {
        this.mensaje = respuesta.status === 401
          ? 'La contraseña y/o el correo no son correctos.'
          : respuesta.error?.message ?? 'No se pudo completar el inicio de sesión. Intenta de nuevo.';
        this.error = true;
        this.cargando = false;
        this.changeDetector.markForCheck();
      },
    });
  }

}
