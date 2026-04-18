<script>
    (function () {
        if (window.InventoryFaceRecognition) {
            return;
        }

        var faceApiScriptUrl = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
        var faceApiModelUrl = 'https://justadudewhohacks.github.io/face-api.js/models';
        var faceApiScriptPromise = null;
        var faceApiModelsPromise = null;

        function loadFaceApiScript() {
            if (window.faceapi) {
                return Promise.resolve(window.faceapi);
            }

            if (faceApiScriptPromise) {
                return faceApiScriptPromise;
            }

            faceApiScriptPromise = new Promise(function (resolve, reject) {
                var existingScript = document.querySelector('script[data-face-api-loader="1"]');

                if (existingScript && window.faceapi) {
                    resolve(window.faceapi);
                    return;
                }

                var script = existingScript || document.createElement('script');

                script.async = true;
                script.src = faceApiScriptUrl;
                script.dataset.faceApiLoader = '1';
                script.onload = function () {
                    if (window.faceapi) {
                        resolve(window.faceapi);
                        return;
                    }

                    reject(new Error('Library face-api.js gagal dimuat.'));
                };
                script.onerror = function () {
                    reject(new Error('Library face-api.js gagal dimuat.'));
                };

                if (!existingScript) {
                    document.head.appendChild(script);
                }
            });

            return faceApiScriptPromise;
        }

        async function loadFaceApiModels() {
            await loadFaceApiScript();

            if (!faceApiModelsPromise) {
                faceApiModelsPromise = Promise.all([
                    window.faceapi.nets.tinyFaceDetector.loadFromUri(faceApiModelUrl),
                    window.faceapi.nets.faceLandmark68Net.loadFromUri(faceApiModelUrl),
                    window.faceapi.nets.faceRecognitionNet.loadFromUri(faceApiModelUrl),
                ]);
            }

            return faceApiModelsPromise;
        }

        function clampShift(value) {
            if (!Number.isFinite(value)) {
                return 0;
            }

            return Math.max(-100, Math.min(100, value));
        }

        function getFrameRatio(frameMode) {
            return frameMode === 'wide' ? 4 / 3 : 1;
        }

        function getCaptureDimensions(captureSize, frameMode) {
            var outputWidth = Math.max(1, Number(captureSize) || 512);
            var targetRatio = getFrameRatio(frameMode);

            return {
                width: outputWidth,
                height: Math.max(1, Math.round(outputWidth / targetRatio)),
                ratio: targetRatio,
            };
        }

        function resolveCropRect(sourceWidth, sourceHeight, targetRatio, horizontalShift, verticalShift) {
            var sourceAspect = sourceWidth / sourceHeight;
            var sourceX = 0;
            var sourceY = 0;
            var cropWidth = sourceWidth;
            var cropHeight = sourceHeight;
            var safeHorizontalShift = clampShift(horizontalShift);
            var safeVerticalShift = clampShift(verticalShift);

            if (sourceAspect > targetRatio) {
                cropHeight = sourceHeight;
                cropWidth = Math.round(cropHeight * targetRatio);

                var horizontalSpace = Math.max(0, sourceWidth - cropWidth);
                sourceX = Math.round((horizontalSpace / 2) + ((safeHorizontalShift / 100) * (horizontalSpace / 2)));
            } else if (sourceAspect < targetRatio) {
                cropWidth = sourceWidth;
                cropHeight = Math.round(cropWidth / targetRatio);

                var verticalSpace = Math.max(0, sourceHeight - cropHeight);
                sourceY = Math.round((verticalSpace / 2) + ((safeVerticalShift / 100) * (verticalSpace / 2)));
            }

            sourceX = Math.max(0, Math.min(sourceWidth - cropWidth, sourceX));
            sourceY = Math.max(0, Math.min(sourceHeight - cropHeight, sourceY));

            return {
                sourceX: sourceX,
                sourceY: sourceY,
                cropWidth: cropWidth,
                cropHeight: cropHeight,
            };
        }

        async function captureFaceData(video, canvas, options) {
            var settings = options || {};
            var includeImage = settings.includeImage !== false;
            var startedAt = typeof performance !== 'undefined' && performance.now
                ? performance.now()
                : Date.now();

            await loadFaceApiModels();

            if (!video || !canvas || video.videoWidth <= 0 || video.videoHeight <= 0) {
                return {
                    status: 'camera_not_ready',
                    debug: {
                        pass: 'none',
                        detectorInputSize: 0,
                        scoreThreshold: 0,
                        fallbackTried: false,
                        fallbackDetectorInputSize: 0,
                        fallbackScoreThreshold: 0,
                        facesDetected: 0,
                        maxScore: 0,
                        processingMs: 0,
                    },
                };
            }

            var captureDimensions = getCaptureDimensions(settings.captureSize, settings.frameMode);
            var cropRect = resolveCropRect(
                video.videoWidth,
                video.videoHeight,
                captureDimensions.ratio,
                settings.horizontalShift,
                settings.verticalShift
            );

            canvas.width = captureDimensions.width;
            canvas.height = captureDimensions.height;

            var context = canvas.getContext('2d');

            function getProcessingMs() {
                var now = typeof performance !== 'undefined' && performance.now
                    ? performance.now()
                    : Date.now();

                return Math.max(0, now - startedAt);
            }

            function getMaxScore(detectedBoxes) {
                return (detectedBoxes || []).reduce(function (maxScore, currentBox) {
                    var currentScore = Number(currentBox && currentBox.score);

                    if (!Number.isFinite(currentScore)) {
                        return maxScore;
                    }

                    return currentScore > maxScore ? currentScore : maxScore;
                }, 0);
            }

            function buildDebugPayload(status, detectionMeta, detectedBoxes, detectionsCount) {
                return {
                    status: status,
                    pass: detectionMeta.pass,
                    detectorInputSize: detectionMeta.detectorInputSize,
                    scoreThreshold: detectionMeta.scoreThreshold,
                    fallbackTried: detectionMeta.fallbackTried,
                    fallbackDetectorInputSize: detectionMeta.fallbackDetectorInputSize,
                    fallbackScoreThreshold: detectionMeta.fallbackScoreThreshold,
                    facesDetected: Math.max(0, Number(detectionsCount) || 0),
                    maxScore: getMaxScore(detectedBoxes),
                    processingMs: Number(getProcessingMs().toFixed(2)),
                };
            }

            function drawCropToCanvas(targetCropRect) {
                context.drawImage(
                    video,
                    targetCropRect.sourceX,
                    targetCropRect.sourceY,
                    targetCropRect.cropWidth,
                    targetCropRect.cropHeight,
                    0,
                    0,
                    captureDimensions.width,
                    captureDimensions.height
                );
            }

            function extractDetectedBoxes(detections) {
                return (detections || []).map(function (detection) {
                    var faceDetection = detection && detection.detection ? detection.detection : null;
                    var detectionBox = null;

                    if (faceDetection && faceDetection.box) {
                        detectionBox = faceDetection.box;
                    } else if (faceDetection && faceDetection._box) {
                        detectionBox = faceDetection._box;
                    }

                    if (!detectionBox) {
                        return null;
                    }

                    var resolvedX = Number.isFinite(Number(detectionBox.x))
                        ? Number(detectionBox.x)
                        : Number(detectionBox._x);
                    var resolvedY = Number.isFinite(Number(detectionBox.y))
                        ? Number(detectionBox.y)
                        : Number(detectionBox._y);
                    var resolvedWidth = Number.isFinite(Number(detectionBox.width))
                        ? Number(detectionBox.width)
                        : Number(detectionBox._width);
                    var resolvedHeight = Number.isFinite(Number(detectionBox.height))
                        ? Number(detectionBox.height)
                        : Number(detectionBox._height);

                    return {
                        x: Number.isFinite(resolvedX) ? resolvedX : 0,
                        y: Number.isFinite(resolvedY) ? resolvedY : 0,
                        width: Number.isFinite(resolvedWidth) ? resolvedWidth : 0,
                        height: Number.isFinite(resolvedHeight) ? resolvedHeight : 0,
                        score: faceDetection && Number.isFinite(Number(faceDetection.score))
                            ? Number(faceDetection.score)
                            : 0,
                    };
                }).filter(function (item) {
                    return item !== null;
                });
            }

            async function detectFacesWithTinyDetector(detectorInputSize, scoreThreshold) {
                var safeInputSize = Math.max(160, Math.min(416, Number(detectorInputSize) || 416));
                var safeScoreThreshold = Number.isFinite(Number(scoreThreshold))
                    ? Number(scoreThreshold)
                    : 0.5;

                safeScoreThreshold = Math.max(0.2, Math.min(0.9, safeScoreThreshold));

                var detections = await window.faceapi
                    .detectAllFaces(
                        canvas,
                        new window.faceapi.TinyFaceDetectorOptions({
                            inputSize: safeInputSize,
                            scoreThreshold: safeScoreThreshold,
                        })
                    )
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                return {
                    detections: detections || [],
                    detectorInputSize: safeInputSize,
                    scoreThreshold: safeScoreThreshold,
                };
            }

            drawCropToCanvas(cropRect);

            var primaryDetectionResult = await detectFacesWithTinyDetector(settings.detectorInputSize, settings.scoreThreshold);
            var detections = primaryDetectionResult.detections;
            var detectedBoxes = extractDetectedBoxes(detections);
            var detectionMeta = {
                pass: 'primary',
                detectorInputSize: primaryDetectionResult.detectorInputSize,
                scoreThreshold: primaryDetectionResult.scoreThreshold,
                fallbackTried: false,
                fallbackDetectorInputSize: 0,
                fallbackScoreThreshold: 0,
            };

            if ((!detections || detections.length === 0) && settings.enableFallbackDetection !== false) {
                var fallbackCropRect = resolveCropRect(
                    video.videoWidth,
                    video.videoHeight,
                    captureDimensions.ratio,
                    0,
                    0
                );
                var fallbackDetectorInputSize = Number.isFinite(Number(settings.fallbackDetectorInputSize))
                    ? Number(settings.fallbackDetectorInputSize)
                    : 320;
                var fallbackScoreThreshold = Number.isFinite(Number(settings.fallbackScoreThreshold))
                    ? Number(settings.fallbackScoreThreshold)
                    : 0.35;
                detectionMeta.fallbackTried = true;
                detectionMeta.fallbackDetectorInputSize = fallbackDetectorInputSize;
                detectionMeta.fallbackScoreThreshold = fallbackScoreThreshold;

                drawCropToCanvas(fallbackCropRect);

                var fallbackDetectionResult = await detectFacesWithTinyDetector(
                    fallbackDetectorInputSize,
                    fallbackScoreThreshold
                );
                var fallbackDetections = fallbackDetectionResult.detections;

                if (fallbackDetections && fallbackDetections.length > 0) {
                    detections = fallbackDetections;
                    detectedBoxes = extractDetectedBoxes(fallbackDetections);
                    detectionMeta.pass = 'fallback';
                    detectionMeta.detectorInputSize = fallbackDetectionResult.detectorInputSize;
                    detectionMeta.scoreThreshold = fallbackDetectionResult.scoreThreshold;
                }
            }

            if ((!detections || detections.length === 0) && detectedBoxes.length === 0) {
                drawCropToCanvas(cropRect);
            }

            if (!detections || detections.length === 0) {
                return {
                    status: 'no_face',
                    detectedBoxes: [],
                    captureDimensions: {
                        width: captureDimensions.width,
                        height: captureDimensions.height,
                    },
                    debug: buildDebugPayload('no_face', detectionMeta, detectedBoxes, 0),
                };
            }

            detectedBoxes = extractDetectedBoxes(detections);

            if (detections.length > 1) {
                return {
                    status: 'multiple_faces',
                    detectedBoxes: detectedBoxes,
                    captureDimensions: {
                        width: captureDimensions.width,
                        height: captureDimensions.height,
                    },
                    debug: buildDebugPayload('multiple_faces', detectionMeta, detectedBoxes, detections.length),
                };
            }

            var descriptor = detections[0].descriptor ? Array.from(detections[0].descriptor) : [];

            if (descriptor.length !== 128) {
                return {
                    status: 'invalid_descriptor',
                    detectedBoxes: detectedBoxes,
                    captureDimensions: {
                        width: captureDimensions.width,
                        height: captureDimensions.height,
                    },
                    debug: buildDebugPayload('invalid_descriptor', detectionMeta, detectedBoxes, detections.length),
                };
            }

            return {
                status: 'ok',
                descriptor: descriptor,
                imageBase64: includeImage ? canvas.toDataURL('image/jpeg', Number(settings.imageQuality) || 0.85) : '',
                detectedBoxes: detectedBoxes,
                captureDimensions: {
                    width: captureDimensions.width,
                    height: captureDimensions.height,
                },
                debug: buildDebugPayload('ok', detectionMeta, detectedBoxes, detections.length),
            };
        }

        window.InventoryFaceRecognition = {
            loadFaceApiModels: loadFaceApiModels,
            captureFaceData: captureFaceData,
            getCaptureDimensions: getCaptureDimensions,
        };
    })();
</script>