import { Routes } from '@angular/router';
import { Login } from './modules/login/login';
import { Registro } from './modules/registro/registro';
import { Inicio } from './modules/inicio/inicio';
import { Visitantes } from './modules/visitantes/visitantes';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login, title: 'Iniciar sesión' },
  { path: 'registro', component: Registro, title: 'Crear cuenta' },
  { path: 'inicio', component: Inicio, title: 'Inicio' },
  { path: 'visitantes', component: Visitantes, title: 'Preautorizar visitante' },
  { path: '**', redirectTo: 'login' },
];
