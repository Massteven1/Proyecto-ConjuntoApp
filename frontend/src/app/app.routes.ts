import { Routes } from '@angular/router';
import { Login } from './modules/login/login';
import { Registro } from './modules/registro/registro';
import { Inicio } from './modules/inicio/inicio';
import { Visitantes } from './modules/visitantes/visitantes';
import { Porteria } from './modules/porteria/porteria';
import { Zonas } from './modules/zonas/zonas';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login, title: 'Iniciar sesión' },
  { path: 'registro', component: Registro, title: 'Crear cuenta' },
  { path: 'inicio', component: Inicio, title: 'Inicio' },
  { path: 'visitantes', component: Visitantes, title: 'Preautorizar visitante' },
  { path: 'porteria', component: Porteria, title: 'Porteria' },
  { path: 'zonas', component: Zonas, title: 'Zonas comunes' },
  { path: '**', redirectTo: 'login' },
];
