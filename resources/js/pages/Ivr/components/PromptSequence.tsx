import { ArrowDown, ArrowUp, GripVertical, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface IvrPrompt {
    id: number;
    originalName: string;
    filename: string;
    position: number;
    sizeBytes: number;
    uploadedAt: string | null;
}

/**
 * What this prompt's file will be called once the pending order is saved.
 * The numeric prefix is what the telephony system reads, so a reorder renames
 * files — showing the projected name makes that consequence visible first.
 */
export function projectedFilename(prompt: IvrPrompt, index: number): string {
    const prefix = String(index + 1).padStart(3, '0');

    return prompt.filename.replace(/^\d{3}-/, `${prefix}-`);
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function PromptSequence({
    prompts,
    onReorder,
    onDelete,
    disabled,
}: {
    prompts: IvrPrompt[];
    onReorder: (next: IvrPrompt[]) => void;
    onDelete: (prompt: IvrPrompt) => void;
    disabled: boolean;
}) {
    const [draggingIndex, setDraggingIndex] = useState<number | null>(null);

    const move = (from: number, to: number) => {
        if (to < 0 || to >= prompts.length || from === to) {
            return;
        }

        const next = [...prompts];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        onReorder(next);
    };

    return (
        <ol className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
            {prompts.map((prompt, index) => {
                const projected = projectedFilename(prompt, index);
                const renaming = projected !== prompt.filename;

                return (
                    <li
                        key={prompt.id}
                        draggable={!disabled}
                        onDragStart={() => setDraggingIndex(index)}
                        onDragEnd={() => setDraggingIndex(null)}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => {
                            e.preventDefault();

                            if (draggingIndex !== null) {
                                move(draggingIndex, index);
                            }

                            setDraggingIndex(null);
                        }}
                        className={cn(
                            'flex items-center gap-4 py-3 transition-opacity motion-reduce:transition-none',
                            draggingIndex === index && 'opacity-50',
                        )}
                    >
                        <GripVertical
                            className="size-4 shrink-0 cursor-grab text-muted-foreground/60"
                            aria-hidden="true"
                        />

                        <span className="shrink-0 rounded-md bg-muted px-2 py-1 font-mono text-xs text-muted-foreground tabular-nums">
                            {String(index + 1).padStart(3, '0')}
                        </span>

                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">
                                {prompt.originalName}
                            </p>
                            <p
                                className={cn(
                                    'truncate font-mono text-xs',
                                    renaming
                                        ? 'text-foreground/70'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {projected}
                                {renaming && (
                                    <span className="ms-2 text-muted-foreground">
                                        was {prompt.filename}
                                    </span>
                                )}
                            </p>
                        </div>

                        <span className="hidden shrink-0 font-mono text-xs text-muted-foreground sm:block">
                            {formatSize(prompt.sizeBytes)}
                        </span>

                        <div className="flex shrink-0 items-center">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                disabled={disabled || index === 0}
                                aria-label={`Move ${prompt.originalName} earlier`}
                                onClick={() => move(index, index - 1)}
                            >
                                <ArrowUp
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Button>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                disabled={
                                    disabled || index === prompts.length - 1
                                }
                                aria-label={`Move ${prompt.originalName} later`}
                                onClick={() => move(index, index + 1)}
                            >
                                <ArrowDown
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Button>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                disabled={disabled}
                                aria-label={`Remove ${prompt.originalName}`}
                                onClick={() => onDelete(prompt)}
                            >
                                <Trash2 className="size-4" aria-hidden="true" />
                            </Button>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
