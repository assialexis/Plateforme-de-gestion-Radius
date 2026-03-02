import whisper
import sys

def transcribe(audio_path):
    print(f"Loading model...", file=sys.stderr)
    model = whisper.load_model("base")
    print(f"Transcribing {audio_path}...", file=sys.stderr)
    result = model.transcribe(audio_path)
    print("Transcription:")
    print(result["text"])

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python transcribe_audio.py <audio_file>")
        sys.exit(1)
    transcribe(sys.argv[1])
