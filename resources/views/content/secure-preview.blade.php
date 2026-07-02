<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $content->content_title }} Preview</title>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            background: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .preview-shell {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 100vh;
            background: #111827;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 74px;
            padding: 12px 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            z-index: 2;
        }

        .preview-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .preview-title-wrap {
            min-width: 0;
        }

        .preview-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 18px;
            line-height: 1.2;
            font-weight: 700;
            color: #0f172a;
        }

        .preview-subtitle {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #64748b;
            text-transform: uppercase;
        }

        .preview-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .preview-pill {
            display: inline-flex;
            align-items: center;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }


        .preview-frame-wrap {
            flex: 1;
            min-height: 0;
            padding: 28px 32px 32px;
            background:
                radial-gradient(circle at top, rgba(59, 130, 246, 0.12), transparent 30%),
                #111827;
            overflow: hidden;
        }

        .preview-stage {
            width: 100%;
            height: 100%;
            max-width: 1180px;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 14px;
            background: #1f2937;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.34);
        }

        .preview-stage::before {
            content: "";
            display: block;
            height: 8px;
            background: linear-gradient(90deg, #2563eb, #14b8a6);
        }

        .preview-frame-holder {
            height: calc(100% - 8px);
            background: #ffffff;
        }

        .preview-frame {
            width: 100%;
            height: 100%;
            border: 0;
            background: #ffffff;
        }

        .preview-shell:fullscreen .preview-toolbar {
            min-height: 58px;
            padding: 10px 18px;
            background: #0f172a;
            border-color: rgba(148, 163, 184, 0.24);
            box-shadow: none;
        }

        .preview-shell:fullscreen .preview-title {
            color: #ffffff;
        }

        .preview-shell:fullscreen .preview-subtitle {
            color: #94a3b8;
        }

        .preview-shell:fullscreen .preview-pill {
            border-color: rgba(96, 165, 250, 0.32);
            background: rgba(37, 99, 235, 0.14);
            color: #bfdbfe;
        }

            .preview-shell:fullscreen .preview-frame-wrap {
            padding: 18px 20px 22px;
        }

        .preview-shell:fullscreen .preview-stage {
            max-width: none;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .preview-toolbar {
                align-items: flex-start;
                flex-direction: column;
                min-height: auto;
                padding: 14px;
            }

            .preview-heading {
                width: 100%;
            }

            .preview-actions {
                width: 100%;
                justify-content: space-between;
            }

            .preview-frame-wrap {
                padding: 12px;
            }

            .preview-stage {
                border-radius: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-shell" id="previewShell">
        <div class="preview-toolbar">
            <div class="preview-heading">
                <div class="preview-title-wrap">
                    <div class="preview-title">{{ $content->content_title }}</div>
                    <div class="preview-subtitle">Secure student preview</div>
                </div>
            </div>

            <div class="preview-actions">
                <span class="preview-pill">View only</span>
            </div>
        </div>

        <div class="preview-frame-wrap">
            <div class="preview-stage">
                <div class="preview-frame-holder">
                    <iframe class="preview-frame"
                            src="{{ $sourceUrl }}#toolbar=0&navpanes=0&scrollbar=1&zoom=page-width"
                            allow="fullscreen"
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


