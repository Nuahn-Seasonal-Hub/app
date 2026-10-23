import requests, re, sys
BASE='http://127.0.0.1:8091'
class C:
    def __init__(s, email=None, pw='password123', role=None):
        s.s=requests.Session(); s.s.headers['User-Agent']='flows'
        if email: s.login(email,pw,role)
    def tok(s, html):
        m=re.search(r'name="_csrf" value="([^"]+)"', html); return m.group(1) if m else None
    def get(s, path, **kw): return s.s.get(BASE+'/'+path.lstrip('/'), allow_redirects=kw.pop('allow_redirects',True), **kw)
    def csrf(s, page='public/login.php'):
        return s.tok(s.get(page).text)
    def login(s, email, pw, role=None):
        t=s.csrf()
        d={'email':email,'password':pw,'_csrf':t}
        if role: d['role']=role
        r=s.s.post(BASE+'/actions/login_action.php', data=d, allow_redirects=False)
        return r
    def post(s, do, data=None, files=None, page=None, csrf=True, follow=False):
        d=dict(data or {}); d['do']=do; d.setdefault('back', (page or 'public/bookings.php'))
        if csrf:
            d['_csrf']=None
            for pg in [page or 'public/bookings.php','public/post_task.php','public/open_jobs.php','public/operations.php','public/contractor_setup.php']:
                d['_csrf']=s.tok(s.get(pg).text)
                if d['_csrf']: break
        return s.s.post(BASE+'/actions/marketplace.php', data=d, files=files, allow_redirects=follow)
