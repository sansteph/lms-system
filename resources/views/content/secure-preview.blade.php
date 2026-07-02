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
            background: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .preview-shell {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid #dee2e6;
            background: #ffffff;
        }

        .preview-title {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 600;
            color: #212529;
        }

        .preview-button {
            border: 1px solid #0d6efd;
            border-radius: 6px;
            background: #0d6efd;
            color: #ffffff;
            cursor: pointer;
            font-size: 13px;
            padding: 7px 10px;
            white-space: nowrap;
        }

        .preview-frame-wrap {
            flex: 1;
            min-height: 0;
            background: #e9ecef;
        }

        .preview-frame {
            width: 100%;
            height: 100%;
            border: 0;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <div class="preview-shell" id="previewShell">
        <div class="preview-toolbar">
            <div class="preview-title">{{ $content->content_title }}</div>

            @if($allowFullscreen)
                <button type="button" class="preview-button" id="fullscreenButton">
                    Full screen
                </button>
            @endif
        </div>

        <div class="preview-frame-wrap">
            <iframe class="preview-frame"
                    src="{{ $sourceUrl }}#toolbar=0&navpanes=0&scrollbar=1"
                    allowfullscreen>
            </iframe>
        </div>
    </div>

    @if($allowFullscreen)
        <script>
            const fullscreenButton = document.getElementById('fullscreenButton');
            const previewShell = document.getElementById('previewShell');

            fullscreenButton.addEventListener('click', () => {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                    return;
                }

                previewShell.requestFullscreen();
            });
        </script>
    @endif
</body>
</html>
