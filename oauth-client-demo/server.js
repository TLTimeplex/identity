import express from 'express';
import session from 'express-session';
import * as oidc from 'openid-client';

const requiredEnvironment = [
  'BASE_URL',
  'OIDC_ISSUER',
  'OIDC_CLIENT_ID',
  'OIDC_CLIENT_SECRET',
  'SESSION_SECRET',
];

for (const name of requiredEnvironment) {
  if (!process.env[name] || process.env[name].startsWith('replace-with-')) {
    throw new Error(`Set ${name} in oauth-client-demo/.env before starting the demo.`);
  }
}

const port = Number(process.env.PORT || 3000);
const baseUrl = new URL(process.env.BASE_URL);
const issuerUrl = new URL(process.env.OIDC_ISSUER);
const redirectUri = new URL('/oauth/callback', baseUrl).href;
const config = await oidc.discovery(
  issuerUrl,
  process.env.OIDC_CLIENT_ID,
  undefined,
  oidc.ClientSecretBasic(process.env.OIDC_CLIENT_SECRET),
);

const app = express();

app.disable('x-powered-by');
app.use(session({
  name: 'identity-demo.sid',
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    sameSite: 'lax',
    secure: baseUrl.protocol === 'https:',
    maxAge: 30 * 60 * 1000,
  },
}));

app.use((_request, response, next) => {
  response.set('Cache-Control', 'no-store');
  next();
});

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  })[character]);
}

function page(title, content) {
  return `<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${escapeHtml(title)} · Identity OAuth Demo</title>
    <style>
      :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; background: #f3f5f2; color: #19251f; }
      * { box-sizing: border-box; }
      body { margin: 0; min-height: 100vh; background: radial-gradient(ellipse at 85% 0%, #d8ebe4 0, transparent 38%), #f3f5f2; }
      main { width: min(720px, calc(100% - 32px)); margin: 0 auto; padding: 12vh 0 64px; }
      .eyebrow { color: #39745d; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
      h1 { margin: 12px 0 8px; font-size: clamp(30px, 6vw, 48px); line-height: 1.05; }
      p { color: #526159; line-height: 1.65; }
      section { margin-top: 30px; padding: 24px; border: 1px solid #d6ded8; border-radius: 8px; background: rgb(255 255 255 / 82%); }
      a, button { display: inline-flex; align-items: center; min-height: 44px; padding: 0 18px; border: 0; border-radius: 5px; background: #17694c; color: white; font: inherit; font-weight: 650; text-decoration: none; cursor: pointer; }
      a:hover, button:hover { background: #10553d; }
      fieldset { margin: 0; padding: 16px 0; border: 0; border-bottom: 1px solid #d6ded8; }
      legend { margin-bottom: 8px; font-weight: 700; }
      .choice { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; color: #19251f; line-height: 1.45; }
      .choice input { margin-top: 4px; accent-color: #17694c; }
      .choice small { display: block; margin-top: 3px; color: #526159; }
      .form-actions { margin-top: 20px; }
      pre { overflow: auto; padding: 16px; border-radius: 5px; background: #17221c; color: #d9eee2; font-size: 13px; line-height: 1.55; }
      code { overflow-wrap: anywhere; }
      .error { color: #9b3027; }
      .meta { font-size: 13px; }
    </style>
  </head>
  <body>
    <main>
      <div class="eyebrow">OIDC relying party · local test</div>
      <h1>${escapeHtml(title)}</h1>
      ${content}
    </main>
  </body>
</html>`;
}

app.get('/', (request, response) => {
  const user = request.session.user;

  if (!user) {
    response.type('html').send(page('Mit Identity anmelden', `
      <p>Diese Express-App startet OpenID Connect bei Hydra und verarbeitet den Callback serverseitig.</p>
      <section>
        <p class="meta">Issuer: <code>${escapeHtml(issuerUrl.href)}</code></p>
        <form action="/login" method="get">
          <fieldset>
            <legend>Angeforderte Berechtigungen</legend>
            <input type="hidden" name="scope" value="openid">
            <label class="choice">
              <input type="checkbox" checked disabled>
              <span><strong>openid</strong><small>Für die Anmeldung erforderlich</small></span>
            </label>
            <label class="choice">
              <input type="checkbox" name="scope" value="profile" checked>
              <span><strong>profile</strong><small>Name und Profilinformationen anfordern</small></span>
            </label>
            <label class="choice">
              <input type="checkbox" name="scope" value="email" checked>
              <span><strong>email</strong><small>E-Mail-Adresse und Bestätigungsstatus anfordern</small></span>
            </label>
          </fieldset>
          <fieldset>
            <legend>Consent-Skip</legend>
            <label class="choice">
              <input type="radio" name="skip" value="allow" checked>
              <span><strong>Gespeicherte Freigabe wiederverwenden</strong><small>Hydra darf den Consent-Dialog für bereits freigegebene Scopes überspringen.</small></span>
            </label>
            <label class="choice">
              <input type="radio" name="skip" value="force">
              <span><strong>Consent erneut anzeigen</strong><small>Die Zustimmung wird auch bei einer gespeicherten Freigabe erneut angefordert.</small></span>
            </label>
          </fieldset>
          <div class="form-actions">
            <button type="submit">Mit Identity anmelden</button>
          </div>
        </form>
      </section>`));

    return;
  }

  response.type('html').send(page('Anmeldung erfolgreich', `
    <p>Hydra hat den Authorization Code gegen Tokens getauscht und die OIDC-Claims validiert.</p>
    <section>
      <p>Angemeldeter Subject: <strong>${escapeHtml(user.sub || 'unbekannt')}</strong></p>
      <pre>${escapeHtml(JSON.stringify(user, null, 2))}</pre>
      <a href="/logout">Abmelden</a>
    </section>`));
});

app.get('/login', async (request, response, next) => {
  try {
    const allowedScopes = new Set(['openid', 'profile', 'email']);
    const scopeParameter = request.query.scope;
    const selectedScopes = scopeParameter === undefined
      ? ['openid', 'profile', 'email']
      : Array.isArray(scopeParameter)
        ? scopeParameter
        : [scopeParameter];
    const requestedScopes = [...new Set(
      selectedScopes.filter((scope) => typeof scope === 'string' && allowedScopes.has(scope)),
    )];

    if (!requestedScopes.includes('openid')) {
      requestedScopes.unshift('openid');
    }

    const codeVerifier = oidc.randomPKCECodeVerifier();
    const codeChallenge = await oidc.calculatePKCECodeChallenge(codeVerifier);
    const state = oidc.randomState();
    const nonce = oidc.randomNonce();

    request.session.oidc = { codeVerifier, state, nonce };

    const authorizationUrl = oidc.buildAuthorizationUrl(config, {
      redirect_uri: redirectUri,
      response_type: 'code',
      scope: requestedScopes.join(' '),
      code_challenge: codeChallenge,
      code_challenge_method: 'S256',
      state,
      nonce,
      ...(request.query.skip === 'force' ? { prompt: 'consent' } : {}),
    });

    response.redirect(authorizationUrl.href);
  } catch (error) {
    next(error);
  }
});

app.get('/oauth/callback', async (request, response, next) => {
  const currentUrl = new URL(request.originalUrl, baseUrl);
  const oauthError = currentUrl.searchParams.get('error');

  if (oauthError) {
    request.session.oidc = null;
    response.status(400).type('html').send(page('Anmeldung abgelehnt', `
      <section>
        <p class="error"><strong>${escapeHtml(oauthError)}</strong></p>
        <p>${escapeHtml(currentUrl.searchParams.get('error_description') || 'Hydra hat die Anmeldung abgelehnt.')}</p>
        <a href="/login">Erneut versuchen</a>
      </section>`));

    return;
  }

  const pendingLogin = request.session.oidc;

  if (!pendingLogin) {
    response.status(400).type('html').send(page('Sitzung fehlt', `
      <section>
        <p class="error">Der Callback gehört zu keiner laufenden Anmeldung. Starte den Login erneut.</p>
        <a href="/login">Login starten</a>
      </section>`));

    return;
  }

  try {
    const tokens = await oidc.authorizationCodeGrant(config, currentUrl, {
      pkceCodeVerifier: pendingLogin.codeVerifier,
      expectedState: pendingLogin.state,
      expectedNonce: pendingLogin.nonce,
    });
    const claims = tokens.claims();

    await new Promise((resolve, reject) => {
      request.session.regenerate((error) => error ? reject(error) : resolve());
    });

    request.session.user = {
      sub: claims.sub,
      name: claims.name,
      email: claims.email,
      email_verified: claims.email_verified,
      issuer: claims.iss,
    };

    response.redirect('/');
  } catch (error) {
    next(error);
  }
});

app.get('/logout', (request, response, next) => {
  request.session.destroy((error) => {
    if (error) {
      next(error);

      return;
    }

    response.clearCookie('identity-demo.sid', { path: '/' });
    response.redirect('/');
  });
});

app.use((error, _request, response, next) => {
  if (response.headersSent) {
    next(error);

    return;
  }

  console.error(error);
  response.status(500).type('html').send(page('OIDC-Fehler', `
    <section>
      <p class="error">${escapeHtml(error.message || 'Unbekannter Fehler')}</p>
      <a href="/">Zur Startseite</a>
    </section>`));
});

app.listen(port, () => {
  console.log(`Identity OAuth demo listening at ${baseUrl.href}`);
});
