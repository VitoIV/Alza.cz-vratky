# Return Intelligence Suite (PHP Edition)

Tento projekt dodává kompletní proof-of-concept řešení pro kategorizaci vratek podle souboru `odstoupeni.xlsx` bez potřeby Pythonu, Redis fronty ani kontejnerových workerů. Celý workflow běží čistě na PHP, takže je vhodný i pro klasický webhosting.

## 1. Architektura

| Vrstva | Technologie | Popis |
| --- | --- | --- |
| Web UI | PHP 8 (bez frameworku), HTML, CSS, JS | Tmavý administrační panel s dashboardem, správou batchů, taxonomie, slovníku a návrhů. |
| Databáze | MySQL / MariaDB | Perzistence týmů, kategorií, root cause tagů, záznamů batchů a agregovaných issues. |
| GPT | OpenAI gpt-5.0-mini | Jemná klasifikace pouze tam, kde nestačí slovníček nebo deduplikace. |

Klíčové vlastnosti:

- **Taxonomie**: 4 týmy (`CONTENT`, `QUALITY`, `LOGISTICS`, `CUSTOMER`) s detailními kategoriemi a root cause tagy.
- **Slovníček**: Multi-jazyčný seznam frází, které se zpracují lokálně (90% shoda bez ohledu na pořadí slov).
- **Batch workflow**: XLSX → databáze → manuálně spuštěné zpracování → GPT klasifikace → agregace duplicit (mimo CONTENT).
- **Návrhy**: GPT může navrhnout nové kategorie/root cause; návrhy se objeví v sekci „Zpracovat ručně“.
- **Observabilita**: Progress bar, hlášky o backoffu, log per batch (tlačítko „Log“).

## 2. Konfigurace

V adresáři `config/` je připraveno `app.dist.php`. Zkopírujte jej na `app.php` a upravte hodnoty:

```php
<?php
return [
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'returns',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
    ],
    'openai' => [
        'api_key' => 'sk-...',
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-5.0-mini',
        'request_timeout' => 30,
    ],
    'processing' => [
        'chunk_size' => 3,
        'rate_limit_backoff' => [60, 180, 300],
    ],
    'app' => [
        'timezone' => 'Europe/Prague',
    ],
];
```

Soubor `config/app.php` se necommitne do repozitáře, takže API klíč zůstane mimo git.

## 3. Inicializace databáze

Přihlaste se do MySQL/MariaDB a spusťte:

```sql
CREATE DATABASE returns CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE returns;
SOURCE database/schema.sql;
SOURCE database/seed_taxonomy.sql;
SOURCE database/seed_glossary.sql;
```

Schéma zahrnuje všechny tabulky pro batche, slovníček, návrhy a agregované issues.

## 4. Nasazení na webhosting

1. Nahrajte adresář `app/` a `config/`, `database/`, `odstoupeni.xlsx` na server (např. přes SFTP).
2. Nastavte document root na `app/public`.
3. Ujistěte se, že PHP má povolený `pdo_mysql`, `mbstring`, `curl`, `zip` a `json`.
4. Umožněte zápis do `app/storage/uploads` a `app/storage/logs`.
5. Nastavte `config/app.php` podle vašeho prostředí.
6. Přihlaste se do administrace a nahrajte `odstoupeni.xlsx` nebo vlastní export.

## 5. Workflow zpracování

1. **Nahrání souboru** – přes `/batches/upload` vyberete XLSX (používá vlastní reader, není potřeba další knihovna).
2. **Start zpracování** – na detailu batche klikněte na „Start“. Skript začne postupně zpracovávat záznamy:
   - Slovníček (90% shoda bez ohledu na pořadí slov) zachytí triviální důvody bez volání GPT.
   - Duplicitní texty v rámci jednoho batche se přebírají z prvního vyhodnocení.
   - Zbytek jde do GPT s generovaným promtem dle aktuální taxonomie.
3. **Rate limit handling** – pokud API vrátí `429`, běží backoff 60 → 180 → 300 vteřin. Po třetím selhání se batch pozastaví a UI zobrazí upozornění.
4. **Agregace** – po dokončení batche vzniknou issues (CONTENT se neslučuje, ostatní ano). Detail issue obsahuje tabulku s RMA a kódem produktu všech sloučených záznamů.
5. **Log** – tlačítko „Log“ na detailu batche vypíše poslední zápisy z `storage/logs/batch-{id}.log`.

Zpracování běží v prohlížeči (AJAX smyčka). Pokud potřebujete automatizaci, lze volat `POST /batches/{id}/process` periodicky například přes cron.

## 6. Taxonomie, slovníček a návrhy

- **Taxonomie** – v sekci „Taxonomy“ upravíte definice týmů, kategorií a root cause tagů. Každá změna se okamžitě promítne do promptu.
- **Slovníček** – definice frází fungují vícejazyčně. Normalizace třídí slova v frázi, abyste se nemuseli starat o pořadí.
- **Návrhy** – pokud GPT navrhne novou kategorii/root cause, objeví se v „Proposals“. Schválení vytvoří záznam v taxonomii a příště se použije automaticky.

## 7. Logika klasifikace

1. **Prázdný text** → označí se jako neakční a `needs_review=true`.
2. **Slovníček** → přímo přidělí tým/kategorii/root cause.
3. **Duplicitní hash** → zkopíruje výsledek z první shody.
4. **GPT** → model dostane generovaný prompt s definicemi z databáze a vrátí JSON. Výstup se validuje (pokud chybí klíče, záznam se označí `needs_review`).
5. **Backoff** → při `429` se záznam vrátí do `pending` a batch přejde do čekání.

## 8. Nasazovací kroky (shrnutí)

1. `git clone` nebo upload archivu na hosting.
2. Zkopírujte `config/app.dist.php` → `config/app.php` a doplňte přístupy.
3. Spusťte SQL skripty `schema.sql`, `seed_taxonomy.sql`, `seed_glossary.sql`.
4. Zkontrolujte oprávnění na `app/storage/*`.
5. Otevřete `https://váš-host/app/public` (nebo dle nastavení) a přidejte první batch.
6. Sledujte dashboard, zpracujte návrhy, exportujte výsledky do CSV (přes filtry/tabulku).

## 9. Tipy pro produkční nasazení

- Pokud chcete plnou automatizaci, nastavte cron (např. každou minutu) na URL `POST /batches/{id}/process`.
- Při větších datech můžete zvýšit `processing.chunk_size` (počet záznamů na jeden request).
- Doporučujeme logovat přístup přes HTTPS a chránit aplikaci pomocí Basic Auth / SSO na úrovni webserveru.

---

Repozitář obsahuje i referenční dataset `odstoupeni.xlsx`, taxonomii a slovníček založený na analýze všech 15 298 záznamů.
