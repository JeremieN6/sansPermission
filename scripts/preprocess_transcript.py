import sys
import re

def clean_text(text):
    # Supprimer les balises HTML
    text = re.sub(r'<.*?>', '', text)
    # Supprimer les espaces supplémentaires
    text = re.sub(r'\s+', ' ', text)
    return text

def main():
    if len(sys.argv) < 2:
        print("Usage: preprocess_transcript.py <input_file>")
        sys.exit(1)
    
    # Lire le chemin du fichier d'entrée à partir des arguments de la ligne de commande
    input_file = sys.argv[1]

    with open(input_file, 'r', encoding='utf-8') as file:
        content = file.read()

    cleaned_content = clean_text(content)

    output_file = input_file.replace('.txt', '_cleaned.txt')
    with open(output_file, 'w', encoding='utf-8') as file:
        file.write(cleaned_content)

    print(f"Cleaned content saved to {output_file}")

if __name__ == "__main__":
    main()
