# Pub Kviz API

REST API backend aplikacija za praćenje Pub Kviza, izgrađena na Laravel 11 framework-u.

---

## 📋 Sadržaj

- [Opis projekta](#opis-projekta)
- [Tehnologije](#tehnologije)
- [Instalacija](#instalacija)
- [Konfiguracija](#konfiguracija)
- [Pokretanje](#pokretanje)
- [API Dokumentacija](#api-dokumentacija)
- [Testiranje](#testiranje)

---

## Opis projekta

Aplikacija omogućava organizaciju i praćenje takmičenja u formatu Pub Kviza. Sistem podržava:

- Upravljanje sezonama takmičenja
- Registraciju i praćenje timova
- Kreiranje kviz večeri kao događaja
- Unos i automatsko ažuriranje rezultata i scoreboardа
- Token-baziranu autentifikaciju korisnika
- Reset zaboravljene lozinke
- Export podataka u CSV format
- Paginaciju i filtriranje svih lista

---

## Tehnologije

- **PHP** 8.4
- **Laravel** 11
- **Laravel Sanctum** – autentifikacija
- **MySQL** – baza podataka
- **Composer** – upravljanje zavisnostima

---

## Instalacija

### Preduslovi

- PHP >= 8.2
- Composer
- MySQL

### Koraci

**1. Kloniraj repozitorijum**

```bash
git clone https://github.com/korisnik/pub-kviz.git
cd pub-kviz
```

**2. Instaliraj zavisnosti**

```bash
composer install
```

**3. Kopiraj `.env` fajl**

```bash
cp .env.example .env
```

**4. Generisi application key**

```bash
php artisan key:generate
```

**5. Podesi bazu podataka u `.env`**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pub-kviz
DB_USERNAME=root
DB_PASSWORD=
```

**6. Pokreni migracije**

```bash
php artisan migrate
```

**7. Instaliraj Sanctum**

```bash
php artisan install:api
```

---

## Konfiguracija

### `config/auth.php`

Promeni provider da koristi `Korisnik` model:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Korisnik::class,
    ],
],
```

---

## Pokretanje

```bash
php artisan serve
```

Aplikacija je dostupna na `http://localhost:8000`

---

## API Dokumentacija

Sve rute imaju prefiks `/api/v1`.

### Autentifikacija

| Metoda | Endpoint             | Opis                          | Zaštita |
| ------ | -------------------- | ----------------------------- | ------- |
| POST   | `/auth/registracija` | Registracija korisnika        | Javno   |
| POST   | `/auth/prijava`      | Prijava i dobijanje tokena    | Javno   |
| POST   | `/auth/odjava`       | Odjava i brisanje tokena      | Token   |
| GET    | `/auth/ja`           | Podaci prijavljenog korisnika | Token   |

### Lozinka

| Metoda | Endpoint                 | Opis                 | Zaštita |
| ------ | ------------------------ | -------------------- | ------- |
| POST   | `/lozinka/zaboravljena`  | Slanje koda za reset | Javno   |
| POST   | `/lozinka/verifikuj-kod` | Verifikacija koda    | Javno   |
| POST   | `/lozinka/resetuj`       | Reset lozinke        | Javno   |
| POST   | `/lozinka/promeni`       | Promena lozinke      | Token   |

### Sezone

| Metoda | Endpoint                        | Opis                    | Zaštita |
| ------ | ------------------------------- | ----------------------- | ------- |
| GET    | `/sezone`                       | Lista sezona            | Javno   |
| GET    | `/sezone/aktivna`               | Trenutno aktivna sezona | Javno   |
| GET    | `/sezone/{id}`                  | Detalji sezone          | Javno   |
| GET    | `/sezone/{id}/tabela-rezultata` | Scoreboard sezone       | Javno   |
| POST   | `/sezone`                       | Kreiranje sezone        | Token   |
| PUT    | `/sezone/{id}`                  | Izmena sezone           | Token   |
| DELETE | `/sezone/{id}`                  | Brisanje sezone         | Token   |

### Timovi

| Metoda | Endpoint                             | Opis                   | Zaštita |
| ------ | ------------------------------------ | ---------------------- | ------- |
| GET    | `/timovi`                            | Lista timova           | Javno   |
| GET    | `/timovi/{id}`                       | Detalji tima           | Javno   |
| GET    | `/timovi/{id}/statistike`            | Statistike tima        | Javno   |
| POST   | `/timovi`                            | Registracija tima      | Token   |
| PUT    | `/timovi/{id}`                       | Izmena tima            | Token   |
| DELETE | `/timovi/{id}`                       | Brisanje tima          | Token   |
| POST   | `/timovi/{id}/registracija/{sezona}` | Prijava tima za sezonu | Token   |

### Događaji

| Metoda | Endpoint                             | Opis                 | Zaštita |
| ------ | ------------------------------------ | -------------------- | ------- |
| GET    | `/dogadjaji/aktivni`                 | Svi aktivni događaji | Javno   |
| GET    | `/sezone/{id}/dogadjaji`             | Događaji u sezoni    | Javno   |
| GET    | `/sezone/{id}/dogadjaji/{id}`        | Detalji događaja     | Javno   |
| POST   | `/sezone/{id}/dogadjaji`             | Kreiranje događaja   | Token   |
| PUT    | `/sezone/{id}/dogadjaji/{id}`        | Izmena događaja      | Token   |
| DELETE | `/sezone/{id}/dogadjaji/{id}`        | Brisanje događaja    | Token   |
| PATCH  | `/sezone/{id}/dogadjaji/{id}/status` | Promena statusa      | Token   |

### Rezultati

| Metoda | Endpoint                                      | Opis               | Zaštita |
| ------ | --------------------------------------------- | ------------------ | ------- |
| GET    | `/sezone/{id}/dogadjaji/{id}/rezultati`       | Lista rezultata    | Javno   |
| GET    | `/sezone/{id}/dogadjaji/{id}/rezultati/{id}`  | Detalji rezultata  | Javno   |
| POST   | `/sezone/{id}/dogadjaji/{id}/rezultati`       | Unos rezultata     | Token   |
| POST   | `/sezone/{id}/dogadjaji/{id}/rezultati/batch` | Masovni unos       | Token   |
| PUT    | `/sezone/{id}/dogadjaji/{id}/rezultati/{id}`  | Izmena rezultata   | Token   |
| DELETE | `/sezone/{id}/dogadjaji/{id}/rezultati/{id}`  | Brisanje rezultata | Token   |

### Export CSV

| Metoda | Endpoint                                       | Opis               | Zaštita |
| ------ | ---------------------------------------------- | ------------------ | ------- |
| GET    | `/export/timovi`                               | Export timova      | Token   |
| GET    | `/export/sezone/{id}/tabela-rezultata`         | Export scoreboardа | Token   |
| GET    | `/export/sezone/{id}/dogadjaji`                | Export događaja    | Token   |
| GET    | `/export/sezone/{id}/dogadjaji/{id}/rezultati` | Export rezultata   | Token   |

---

### Query parametri za filtriranje

**Sezone** `GET /sezone`

```
?aktivna=1          → samo aktivne sezone
?naziv=2025         → pretraga po nazivu
?od_datuma=2025-01-01
?do_datuma=2025-12-31
?sort=datum_pocetka&smer=desc
?po_stranici=10&stranica=1
```

**Timovi** `GET /timovi`

```
?naziv=sove         → pretraga po nazivu
?aktivan=1          → samo aktivni timovi
?sezona_id=1        → timovi iz određene sezone
?sort=naziv&smer=asc
?po_stranici=10&stranica=1
```

**Događaji** `GET /sezone/{id}/dogadjaji`

```
?status=nadolazeci  → nadolazeci | u_toku | zavrsen
?naziv=kviz         → pretraga po nazivu
?od_datuma=2025-10-01
?do_datuma=2025-12-31
?sort=datum_dogadjaja&smer=asc
?po_stranici=10&stranica=1
```

---

### Format odgovora

**Uspešan odgovor:**

```json
{
    "uspesno": true,
    "poruka": "Opis akcije.",
    "podaci": {}
}
```

**Odgovor sa greškom:**

```json
{
    "uspesno": false,
    "poruka": "Opis greške.",
    "greske": {}
}
```

### HTTP status kodovi

| Kod | Opis                   |
| --- | ---------------------- |
| 200 | Uspešan zahtev         |
| 201 | Resurs kreiran         |
| 401 | Neautorizovan pristup  |
| 404 | Resurs nije pronađen   |
| 405 | Metoda nije dozvoljena |
| 422 | Validaciona greška     |
| 500 | Greška servera         |

---

## Testiranje

### Postman

Importuj kolekciju `PubKviz.postman_collection.json` u Postman.

Postavi `base_url` varijablu na `http://localhost:8000/api/v1`.

**Redosled testiranja:**

1. Registracija korisnika → sačuvaj token
2. Kreiranje sezone
3. Kreiranje timova
4. Prijava timova za sezonu
5. Kreiranje događaja
6. Unos rezultata (batch)
7. Provera scoreboardа
8. Export CSV

### Struktura projekta

```
app/
├── Exceptions/
│   └── Handler.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── AutentifikacijaController.php
│   │   ├── DogadjajController.php
│   │   ├── ExportController.php
│   │   ├── LozinkaController.php
│   │   ├── RezultatController.php
│   │   ├── SezonaController.php
│   │   └── TimController.php
│   └── Resources/
│       └── ApiResponse.php
└── Models/
    ├── Dogadjaj.php
    ├── Korisnik.php
    ├── RezultatDogadjaja.php
    ├── Sezona.php
    └── Tim.php

database/
└── migrations/
    ├── ..._create_sezone_table.php
    ├── ..._create_timovi_table.php
    ├── ..._create_tim_sezona_table.php
    ├── ..._create_dogadjaji_table.php
    ├── ..._create_rezultati_dogadjaja_table.php
    ├── ..._create_korisnici_table.php
    ├── ..._create_reset_lozinke_table.php
    ├── ..._add_adresa_to_timovi_table.php
    ├── ..._rename_opis_in_dogadjaji_table.php
    ├── ..._drop_kontakt_telefon_from_timovi_table.php
    ├── ..._add_constraints_to_rezultati_dogadjaja_table.php
    ├── ..._create_lokacije_table.php
    ├── ..._add_foreign_key_lokacija_to_dogadjaji_table.php
    └── ..._add_index_to_sezone_table.php

routes/
└── api.php

bootstrap/
└── app.php
```
