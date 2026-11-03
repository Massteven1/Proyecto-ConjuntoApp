import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class VisitantesService {
  private readonly http = inject(HttpClient);

  //TODO: Cambiar la url de la api a la url que cada quien tenga
  private readonly api = 'http://localhost:8000/controllers/visitantes.php';

  listar() {
    return this.http.get<any>(`${this.api}?control=listar`, { withCredentials: true });
  }

  apartamentos() {
    return this.http.get<any>(`${this.api}?control=apartamentos`, { withCredentials: true });
  }

  preautorizar(datos: any) {
    return this.http.post<any>(`${this.api}?control=preautorizar`, datos, { withCredentials: true });
  }
}
