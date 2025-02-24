# filepath: /c:/other/Mes Projets Dev/Projets/ProjetPerso/Saas/sansPermission/src/Service/youtube_service.py
from googleapiclient.discovery import build
from youtube_transcript_api import YouTubeTranscriptApi
import json
import re

def extract_video_id(url):
    """Extrait l'ID de la vidéo d'une URL YouTube."""
    patterns = [
        r'(?:v=|\/)([0-9A-Za-z_-]{11}).*',  # URLs standards et partagées
        r'(?:shorts\/)([0-9A-Za-z_-]{11})',  # URLs de shorts
    ]
    
    for pattern in patterns:
        match = re.search(pattern, url)
        if match:
            return match.group(1)
    return None

def get_video_info(api_key, video_id):
    """Récupère les informations d'une vidéo spécifique."""
    youtube = build('youtube', 'v3', developerKey=api_key)
    request = youtube.videos().list(
        part='snippet',
        id=video_id
    )
    response = request.execute()
    
    if not response['items']:
        raise ValueError(f"Vidéo non trouvée: {video_id}")
        
    video_info = response['items'][0]['snippet']
    return {
        'title': video_info['title'],
        'description': video_info['description'],
        'publishedAt': video_info['publishedAt']
    }

def get_transcript(video_id):
    """Récupère la transcription d'une vidéo."""
    try:
        # Essayer d'abord en français
        transcript = YouTubeTranscriptApi.get_transcript(video_id, languages=['fr'])
        script = " ".join([item['text'] for item in transcript])
        return script.encode('utf-8').decode('utf-8')
    except:
        try:
            # Si pas de français, essayer en anglais
            transcript = YouTubeTranscriptApi.get_transcript(video_id, languages=['en'])
            script = " ".join([item['text'] for item in transcript])
            return script.encode('utf-8').decode('utf-8')
        except Exception as e:
            print(f"Erreur pour la vidéo {video_id}: {str(e)}")
            return None

def process_video(api_key, url):
    """Traite une vidéo YouTube et retourne ses informations."""
    video_id = extract_video_id(url)
    if not video_id:
        raise ValueError("URL YouTube invalide")
    
    try:
        # Récupérer les informations de la vidéo
        video_info = get_video_info(api_key, video_id)
        transcript = get_transcript(video_id)
        
        if not transcript:
            raise ValueError("Aucune transcription disponible pour cette vidéo")
            
        return {
            'video_id': video_id,
            'title': video_info['title'].encode('utf-8').decode('utf-8'),
            'description': video_info['description'].encode('utf-8').decode('utf-8'),
            'publishedAt': video_info['publishedAt'],
            'transcript': transcript,
            'url': f"https://www.youtube.com/watch?v={video_id}"
        }
        
    except Exception as e:
        raise Exception(f"Erreur lors du traitement de la vidéo: {str(e)}")

if __name__ == "__main__":
    import sys
    if len(sys.argv) != 3:
        print("Usage: python youtube_service.py API_KEY VIDEO_URL")
        sys.exit(1)
        
    api_key = sys.argv[1]
    video_url = sys.argv[2]
    
    try:
        result = process_video(api_key, video_url)
        with open('video_data.json', 'w', encoding='utf-8') as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
        print("Données extraites avec succès")
    except Exception as e:
        print(f"Erreur: {str(e)}")
        sys.exit(1)
