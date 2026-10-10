# Identity OAuth Client Demo

Kleine Express-App zum Testen von Identity als OpenID Connect Provider. Sie nutzt Authorization Code Flow mit PKCE und validiert `state`, `nonce` und die ID-Token-Antwort serverseitig.

## Starten

1. `cp .env.example .env` aus diesem Verzeichnis ausführen.
2. In `.env` das Client-Secret eintragen und für `SESSION_SECRET` einen langen Zufallswert setzen.
3. `npm install` ausführen.
4. `npm run dev` starten und `http://localhost:3000` im Browser öffnen.

Der Hydra-Client muss als Redirect-URI exakt `http://localhost:3000/oauth/callback` erlauben. Diese URI ist beim aktuell eingerichteten Testclient bereits registriert.

Auf der Startseite kannst du `profile` und `email` einzeln anfordern. `openid` bleibt als OIDC-Pflicht-Scope aktiv. Im normalen Modus darf Hydra eine gespeicherte Consent-Freigabe überspringen. Der zweite Modus sendet `prompt=consent` und zeigt den Consent-Dialog erneut; eine vorhandene Login-Session darf dabei weiterverwendet werden.

Das Client-Secret bleibt serverseitig in `.env`; diese Datei und `node_modules` sind von Git ausgeschlossen. Der In-Memory-Session-Store ist nur für lokale Tests gedacht, nicht für Produktion.
