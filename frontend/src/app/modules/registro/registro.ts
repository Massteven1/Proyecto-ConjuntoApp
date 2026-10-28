import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-registro',
  imports: [FormsModule, RouterLink],
  templateUrl: './registro.html',
  styleUrl: './registro.css',
})
export class Registro {
  nombres = '';
  apellidos = '';
  documento = '';
  correo = '';
  telefono = '';
  password = '';

  onSubmit(): void {
    // Aquí se conectará el servicio de registro con el backend PHP.
  }
}
