# The IVR filename prefix is the playback order

IVR prompts are written to disk as `{service package}/NNN-name.wav`, and the zero-padded
numeric prefix is what determines playback order. Re-ordering prompts in the dashboard
renames the files on disk to match.

The telephony system reads each service's directory and plays what it finds in sorted
order. It is not ours to change, and we cannot verify what it does. That rules out the
alternatives we considered: a manifest file listing prompts in order, and an API endpoint
the telephony system would call for the sequence. Both keep filenames stable and make
re-ordering a single small write instead of a cascade of renames — but both require the
telephony side to cooperate, and cooperation we cannot verify is not a foundation. The
filename prefix works against a system that does nothing more than list a directory.

What we accepted in exchange: a re-order is a batch of renames rather than one atomic
write, and a re-order that lands while the telephony system is reading the directory can
be observed mid-flight. Renames go through temporary names in two phases, because a
straight swap of two prompts would otherwise have one clobber the other. Since prompts
change rarely and are edited by an operator rather than automatically, we judged that
window acceptable rather than engineering around it.

The consequence users see is that re-ordering renames files. The dashboard shows each
prompt's projected filename before the order is saved, so that is visible rather than
surprising.

If the telephony system later gains the ability to read a manifest, revisit this: stable
filenames plus one ordered manifest is the better design wherever it is available.
