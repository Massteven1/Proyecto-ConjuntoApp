import { Routes } from '@angular/router';
import { Login } from './modules/login/login';
import { Registro } from './modules/registro/registro';
import { Inicio } from './modules/inicio/inicio';
import { Visitantes } from './modules/visitantes/visitantes';
import { Porteria } from './modules/porteria/porteria';
import { Zonas } from './modules/zonas/zonas';
import { Cuotas } from './modules/cuotas/cuotas';
import { Pagos } from './modules/pagos/pagos';
import { EstadoCuentaComponent } from './modules/estado-cuenta/estado-cuenta';
import { Comunicados } from './modules/comunicados/comunicados';
import { Reservas } from './modules/reservas/reservas';
import { Tablero } from './modules/tablero/tablero';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login, title: 'Iniciar sesión' },
  { path: 'registro', component: Registro, title: 'Crear cuenta' },
  { path: 'inicio', component: Inicio, title: 'Inicio' },
  { path: 'visitantes', component: Visitantes, title: 'Preautorizar visitante' },
  { path: 'porteria', component: Porteria, title: 'Porteria' },
  { path: 'zonas', component: Zonas, title: 'Zonas comunes' },
  { path: 'reservas', component: Reservas, title: 'Reservas de zonas comunes' },
  { path: 'cuotas', component: Cuotas, title: 'Cuotas' },
  { path: 'pagos', component: Pagos, title: 'Registrar pagos' },
  { path: 'estado-cuenta', component: EstadoCuentaComponent, title: 'Estado de cuenta' },
  { path: 'comunicados', component: Comunicados, title: 'Comunicados' },
  { path: 'tablero', component: Tablero, title: 'Tablero' },
  { path: '**', redirectTo: 'login' },
];
