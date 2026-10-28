import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  correo = '';
  password = '';

  onSubmit(): void {
    // Aquí se conectará el servicio de autenticación con el backend PHP.
  }
}
