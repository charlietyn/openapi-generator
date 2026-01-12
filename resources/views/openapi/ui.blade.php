<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenAPI UI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            background: #f8fafc;
            color: #0f172a;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            max-width: 640px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        code {
            display: inline-block;
            background: #f1f5f9;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>OpenAPI Specification</h1>
        <p>
            Download or inspect the generated OpenAPI JSON:
            <a href="{{ $specUrl }}" target="_blank" rel="noopener noreferrer">OpenAPI JSON</a>
        </p>
        <p>
            Spec URL: <code>{{ $specUrl }}</code>
        </p>
    </div>
</body>
</html>
