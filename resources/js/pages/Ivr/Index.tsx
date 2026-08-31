import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { destroy, index, reorder } from '@/routes/ivr';
import PromptSequence from './components/PromptSequence';
import type { IvrPrompt } from './components/PromptSequence';
import PromptUploader, { describeSpec } from './components/PromptUploader';
import type { AudioSpec } from './components/PromptUploader';
import ServiceTabs from './components/ServiceTabs';
import type { IvrService } from './components/ServiceTabs';

interface Props {
    services: IvrService[];
    selectedServiceId: number;
    files: IvrPrompt[];
    audioSpec: AudioSpec;
}

const sameOrder = (a: IvrPrompt[], b: IvrPrompt[]) =>
    a.length === b.length && a.every((prompt, i) => prompt.id === b[i].id);

export default function IvrIndex({
    services,
    selectedServiceId,
    files,
    audioSpec,
}: Props) {
    const [prompts, setPrompts] = useState<IvrPrompt[]>(files);
    const [lastFiles, setLastFiles] = useState<IvrPrompt[]>(files);
    const [saving, setSaving] = useState(false);

    // Server state wins whenever it changes: after an upload, a delete, a saved
    // reorder, or a switch to another service. Inertia hands us a new props
    // object each visit, so an identity check is enough to notice.
    if (files !== lastFiles) {
        setLastFiles(files);
        setPrompts(files);
    }

    const service = services.find((s) => s.id === selectedServiceId);
    const dirty = !sameOrder(prompts, files);

    const saveOrder = () => {
        setSaving(true);

        router.put(
            reorder().url,
            {
                service_id: selectedServiceId,
                order: prompts.map((prompt) => prompt.id),
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Playback order saved.'),
                onFinish: () => setSaving(false),
            },
        );
    };

    const remove = (prompt: IvrPrompt) => {
        router.delete(destroy(prompt.id).url, {
            preserveScroll: true,
            onSuccess: () => toast.success(`Removed ${prompt.originalName}`),
        });
    };

    return (
        <>
            <Head title="IVR prompts" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="IVR prompts"
                    description="Audio a caller hears, in the order it plays. The telephony system reads each service's folder in filename order, so the numbered prefix is what decides playback."
                />

                <ServiceTabs
                    services={services}
                    selectedServiceId={selectedServiceId}
                />

                <PromptUploader
                    serviceId={selectedServiceId}
                    spec={audioSpec}
                />

                <section className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <header className="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                        <div>
                            <h3 className="text-sm font-medium">
                                Call flow
                                {service && (
                                    <span className="ms-2 font-mono text-xs text-muted-foreground">
                                        {service.package}/
                                    </span>
                                )}
                            </h3>
                            <p className="text-xs text-muted-foreground">
                                {prompts.length === 0
                                    ? 'No prompts yet'
                                    : `${prompts.length} prompt${prompts.length === 1 ? '' : 's'}, played top to bottom`}
                            </p>
                        </div>

                        {dirty && (
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={saving}
                                    onClick={() => setPrompts(files)}
                                >
                                    Discard
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    disabled={saving}
                                    onClick={saveOrder}
                                >
                                    {saving ? 'Saving…' : 'Save order'}
                                </Button>
                            </div>
                        )}
                    </header>

                    <div className="px-4">
                        {prompts.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Upload a{' '}
                                {describeSpec(audioSpec).split(' · ')[0]} file
                                above to start this service&rsquo;s call flow.
                            </p>
                        ) : (
                            <PromptSequence
                                prompts={prompts}
                                onReorder={setPrompts}
                                onDelete={remove}
                                disabled={saving}
                            />
                        )}
                    </div>

                    {dirty && (
                        <p className="border-t border-sidebar-border/70 px-4 py-2 text-xs text-muted-foreground dark:border-sidebar-border">
                            Saving renames files on disk to match the new order.
                        </p>
                    )}
                </section>
            </div>
        </>
    );
}

IvrIndex.layout = {
    breadcrumbs: [
        {
            title: 'IVR prompts',
            href: index(),
        },
    ],
};
