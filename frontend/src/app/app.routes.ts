import { Routes } from '@angular/router';
import { Login } from './modules/login/login';
import { Registro } from './modules/registro/registro';
import { Inicio } from './modules/inicio/inicio';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login, title: 'Iniciar sesión' },
  { path: 'registro', component: Registro, title: 'Crear cuenta' },
  { path: 'inicio', component: Inicio, title: 'Inicio' },
  { path: '**', redirectTo: 'login' },
];
