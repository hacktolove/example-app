import { useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/ivr';

export interface AudioSpec {
    sampleRates: number[];
    bitsPerSample: number[];
    channels: number[];
    maxKilobytes: number;
}

/**
 * Describes what will be accepted. An empty list means that property is not
 * constrained, so it is left out rather than shown as an empty requirement.
 */
export function describeSpec(spec: AudioSpec): string {
    const parts = ['WAV'];

    if (spec.sampleRates.length > 0) {
        parts.push(`${spec.sampleRates.join(' or ')} Hz`);
    }

    if (spec.bitsPerSample.length > 0) {
        parts.push(`${spec.bitsPerSample.join(' or ')}-bit`);
    }

    if (spec.channels.length > 0) {
        parts.push(
            spec.channels
                .map((c) => (c === 1 ? 'mono' : c === 2 ? 'stereo' : `${c}-channel`))
                .join(' or '),
        );
    }

    parts.push(`up to ${Math.round(spec.maxKilobytes / 1024)} MB`);

    return parts.join(' · ');
}

export default function PromptUploader({
    serviceId,
    spec,
}: {
    serviceId: number;
    spec: AudioSpec;
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);

    const form = useForm<{ service_id: number; file: File | null }>({
        service_id: serviceId,
        file: null,
    });

    const upload = (file: File) => {
        form.transform((data) => ({ ...data, service_id: serviceId, file }));

        form.post(store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset('file');
                form.clearErrors();

                if (fileInput.current) {
                    fileInput.current.value = '';
                }

                toast.success(`Added ${file.name}`);
            },
        });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const file = fileInput.current?.files?.[0];

        if (file) {
            upload(file);
        }
    };

    return (
        <form onSubmit={submit}>
            <div
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setDragging(false);
                    const file = e.dataTransfer.files?.[0];

                    if (file) {
                        upload(file);
                    }
                }}
                className={[
                    'flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center transition-colors motion-reduce:transition-none',
                    dragging
                        ? 'border-foreground/40 bg-muted/60'
                        : 'border-sidebar-border/70 dark:border-sidebar-border',
                ].join(' ')}
            >
                <Upload
                    className="size-5 text-muted-foreground"
                    aria-hidden="true"
                />

                <div className="space-y-1">
                    <p className="text-sm font-medium">
                        Drop a prompt here, or choose a file
                    </p>
                    <p className="font-mono text-xs text-muted-foreground">
                        {describeSpec(spec)}
                    </p>
                </div>

                <input
                    ref={fileInput}
                    type="file"
                    name="file"
                    accept=".wav,audio/wav,audio/x-wav"
                    className="sr-only"
                    onChange={submit}
                />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={form.processing}
                    onClick={() => fileInput.current?.click()}
                >
                    {form.processing ? 'Uploading…' : 'Choose file'}
                </Button>
            </div>

            <InputError message={form.errors.file} className="mt-2" />
        </form>
    );
}
