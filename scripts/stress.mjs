#!/usr/bin/env node
/**
 * Prueba de carga y estrés sin dependencias externas.
 *
 * Uso:
 *   node scripts/stress.mjs --requests=1000 --concurrency=20
 *   node scripts/stress.mjs --duration=15 --concurrency=50 --expect=200
 *
 * Salida legible por defecto; --json para consumo programático.
 * Código de salida 1 si alguna respuesta se aparta de --expect.
 */

const DEFAULTS = {
    url: 'http://127.0.0.1:8000/api/v1/products?per_page=100&sort=name&direction=desc',
    method: 'GET',
    concurrency: 10,
    requests: 500,
    duration: 0,
    timeout: 10000,
    expect: '200',
};

/** @param {string[]} argv */
function parseArgs(argv) {
    const options = { ...DEFAULTS, headers: {}, body: undefined, json: false };

    for (const raw of argv) {
        if (raw === '--json') {
            options.json = true;
            continue;
        }

        if (raw === '--help' || raw === '-h') {
            options.help = true;
            continue;
        }

        const match = /^--([a-z-]+)(?:=(.*))?$/.exec(raw);

        if (!match) {
            throw new Error(`Argumento no reconocido: ${raw}`);
        }

        const [, key, value] = match;

        switch (key) {
            case 'url':
                options.url = value;
                break;
            case 'method':
                options.method = String(value).toUpperCase();
                break;
            case 'concurrency':
                options.concurrency = Math.max(1, Number(value));
                break;
            case 'requests':
                options.requests = Math.max(1, Number(value));
                break;
            case 'duration':
                options.duration = Math.max(0, Number(value));
                break;
            case 'timeout':
                options.timeout = Math.max(1, Number(value));
                break;
            case 'expect':
                options.expect = value;
                break;
            case 'header': {
                const separator = String(value).indexOf(':');

                if (separator < 1) {
                    throw new Error(`Cabecera con formato invalido: ${value}`);
                }

                options.headers[String(value).slice(0, separator).trim()] = String(value)
                    .slice(separator + 1)
                    .trim();
                break;
            }
            case 'body':
                options.body = value;
                break;
            default:
                throw new Error(`Opcion desconocida: --${key}`);
        }
    }

    if (!options.headers.Accept) {
        options.headers.Accept = 'application/json';
    }

    if (options.body !== undefined && !options.headers['Content-Type']) {
        options.headers['Content-Type'] = 'application/json';
    }

    options.expected = new Set(
        String(options.expect)
            .split(',')
            .map((code) => Number(code.trim()))
            .filter((code) => Number.isFinite(code)),
    );

    return options;
}

function percentile(sorted, p) {
    if (sorted.length === 0) {
        return 0;
    }

    const index = Math.min(sorted.length - 1, Math.ceil((p / 100) * sorted.length) - 1);

    return sorted[index];
}

/** @param {ReturnType<typeof parseArgs>} options */
async function run(options) {
    const latencies = [];
    const statuses = new Map();
    const errors = new Map();
    let issued = 0;
    let failures = 0;
    let unexpected = 0;
    let stop = false;

    if (options.duration > 0) {
        setTimeout(() => {
            stop = true;
        }, options.duration * 1000).unref();
    }

    const startedAt = process.hrtime.bigint();

    async function worker() {
        while (!stop) {
            if (options.duration === 0 && issued >= options.requests) {
                return;
            }

            issued += 1;

            const begin = process.hrtime.bigint();

            try {
                const response = await fetch(options.url, {
                    method: options.method,
                    headers: options.headers,
                    body: options.body,
                    signal: AbortSignal.timeout(options.timeout),
                });

                await response.arrayBuffer();

                latencies.push(Number(process.hrtime.bigint() - begin) / 1e6);
                statuses.set(response.status, (statuses.get(response.status) ?? 0) + 1);

                if (!options.expected.has(response.status)) {
                    unexpected += 1;
                }
            } catch (error) {
                failures += 1;
                const code = error?.name ?? 'Error';
                errors.set(code, (errors.get(code) ?? 0) + 1);
            }
        }
    }

    await Promise.all(Array.from({ length: options.concurrency }, worker));

    const elapsedMs = Number(process.hrtime.bigint() - startedAt) / 1e6;
    const completed = latencies.length;
    latencies.sort((a, b) => a - b);

    return {
        url: options.url,
        method: options.method,
        concurrency: options.concurrency,
        issued,
        completed,
        failures,
        unexpected,
        elapsedMs,
        throughput: elapsedMs > 0 ? (completed / elapsedMs) * 1000 : 0,
        latency: {
            p50: percentile(latencies, 50),
            p90: percentile(latencies, 90),
            p99: percentile(latencies, 99),
            min: latencies[0] ?? 0,
            max: latencies[latencies.length - 1] ?? 0,
        },
        statuses: Object.fromEntries([...statuses.entries()].sort((a, b) => a[0] - b[0])),
        errors: Object.fromEntries(errors),
    };
}

function render(report) {
    const ms = (value) => value.toFixed(1);

    return [
        `Objetivo    : ${report.method} ${report.url}`,
        `Concurrencia: ${report.concurrency}`,
        `Enviadas    : ${report.issued} (completadas ${report.completed})`,
        `Duracion    : ${(report.elapsedMs / 1000).toFixed(2)} s`,
        `Rendimiento : ${report.throughput.toFixed(1)} req/s`,
        `Latencia ms : p50 ${ms(report.latency.p50)} | p90 ${ms(report.latency.p90)} | p99 ${ms(report.latency.p99)} | min ${ms(report.latency.min)} | max ${ms(report.latency.max)}`,
        `Estados     : ${Object.entries(report.statuses).map(([code, total]) => `${code}=${total}`).join(' ') || 'ninguno'}`,
        `Fallos de red: ${report.failures}${report.failures ? ` (${Object.entries(report.errors).map(([name, total]) => `${name}=${total}`).join(' ')})` : ''}`,
        `Fuera de lo esperado: ${report.unexpected}`,
    ].join('\n');
}

const options = parseArgs(process.argv.slice(2));

if (options.help) {
    console.log(
        [
            'node scripts/stress.mjs [opciones]',
            '',
            '  --url=<url>          objetivo (por defecto el listado de productos)',
            '  --method=GET         metodo HTTP',
            '  --header="K: V"      cabecera adicional, repetible',
            '  --body=<json>        cuerpo de la peticion',
            '  --concurrency=10     peticiones simultaneas',
            '  --requests=500       total de peticiones (modo cerrado)',
            '  --duration=0         segundos de carga (modo abierto; 0 lo desactiva)',
            '  --timeout=10000      ms por peticion',
            '  --expect=200         estados aceptados, separados por coma',
            '  --json               salida JSON',
        ].join('\n'),
    );
    process.exit(0);
}

const report = await run(options);

if (options.json) {
    console.log(JSON.stringify(report, null, 2));
} else {
    console.log(render(report));
}

process.exit(report.failures > 0 || report.unexpected > 0 ? 1 : 0);
