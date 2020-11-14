from facebook_scraper import get_posts
import random
import urllib.request



def downloadImageRand():
    fp_target = [
        {'type' : 'fp','target':'drama.sosmed62'},
        {'type' : 'fp','target': 'Twiteek'},
        {'type' : 'fp','target':'allabouttwitt'},
        {'type' : 'group','target':'dramatwitter'}
    ]
    
    target = fp_target[random.randint(0, len(fp_target)-1)]
    data = get_data_item(target)
    for d in data[::-1]:
        if not id_been_post_before(d['id']):
            save_image(d['url'])
            save_id(d['id'])
            return True
    return False
            

def save_id(id):
    with open("saved.txt", "a+") as myfile:
        myfile.write(str(id)+"\n")


def get_ids_saved():
    ids = []
    with open("saved.txt", "r") as myfile:
        ids = [x.replace("\n","") for x in myfile]
    return ids

def id_been_post_before(id):
    if str(id) in get_ids_saved():
        return True
    return False

def save_image(url):
    urllib.request.urlretrieve(url, 'file.png')

def get_data_item(params):
    data = []
    res = []
    if params['type'] == 'fp':
        res = get_posts(params['target'], pages=5)
    else:
        res = get_posts(group = params['target'], pages=5)
        
    for d in res:
        if d['image'] != None:
            data.append({
                'id' : d['post_id'],
                'url' : d['image']
            })
            
    return data




if __name__ == '__main__':
    print(downloadImageRand())