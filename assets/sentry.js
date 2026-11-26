import * as Sentry from '@sentry/browser'

// Le DSN est fourni à l'exécution par le layout Twig (<meta name="sentry-dsn">).
// Sans DSN : aucune initialisation, aucun envoi.
const meta = document.querySelector('meta[name="sentry-dsn"]')
const dsn = meta ? meta.getAttribute('content') : ''

if (dsn) {
  Sentry.init({
    dsn,
    environment: meta.dataset.environment || undefined,
    release: meta.dataset.release || undefined,
    sendDefaultPii: false,
    tracesSampleRate: 0,
    // Ni session replay, ni breadcrumbs (clics, console, requêtes) : ils peuvent contenir des données personnelles
    integrations: (defaults) => defaults.filter((i) => i.name !== 'Breadcrumbs'),
  })
}
