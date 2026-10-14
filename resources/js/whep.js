const WHEP_PREFIX = '/media/api/v1/whep';
const ICE_GATHERING_TIMEOUT = 2000;
const CONNECT_TIMEOUT = 8000;

const openSessions = new Set();

if (typeof window !== 'undefined') {
    window.addEventListener('pagehide', () => {
        Array.from(openSessions).forEach((session) => closeWhepSession(session, true));
    });
}

const waitForIceGathering = (pc) => new Promise((resolve) => {
    if (pc.iceGatheringState === 'complete') {
        resolve();
        return;
    }

    const finish = () => {
        pc.removeEventListener('icegatheringstatechange', check);
        clearTimeout(timer);
        resolve();
    };
    const check = () => {
        if (pc.iceGatheringState === 'complete') finish();
    };
    const timer = setTimeout(finish, ICE_GATHERING_TIMEOUT);

    pc.addEventListener('icegatheringstatechange', check);
});

const waitForConnection = (pc) => new Promise((resolve, reject) => {
    if (pc.connectionState === 'connected') {
        resolve();
        return;
    }

    const cleanup = () => {
        pc.removeEventListener('connectionstatechange', check);
        clearTimeout(timer);
    };
    const check = () => {
        if (pc.connectionState === 'connected') {
            cleanup();
            resolve();
        } else if (pc.connectionState === 'failed') {
            cleanup();
            reject(new Error('WebRTC: соединение не установлено'));
        }
    };
    const timer = setTimeout(() => {
        cleanup();
        reject(new Error('WebRTC: таймаут подключения'));
    }, CONNECT_TIMEOUT);

    pc.addEventListener('connectionstatechange', check);
});

const buildResourceUrl = (uid, profile, location) => {
    if (!location) return null;

    const id = location.split('?')[0].replace(/\/+$/, '').split('/').pop();

    return id ? `${WHEP_PREFIX}/${encodeURIComponent(uid)}/${profile}/${id}` : null;
};

const negotiate = async (uid, profile) => {
    const pc = new RTCPeerConnection();

    pc.addTransceiver('video', { direction: 'recvonly' });
    pc.addTransceiver('audio', { direction: 'recvonly' });

    const stream = new MediaStream();
    pc.addEventListener('track', (event) => stream.addTrack(event.track));

    await pc.setLocalDescription(await pc.createOffer());
    await waitForIceGathering(pc);

    let response;

    try {
        response = await fetch(`${WHEP_PREFIX}/${encodeURIComponent(uid)}/${profile}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/sdp' },
            body: pc.localDescription.sdp,
        });
    } catch (e) {
        pc.close();
        throw e;
    }

    if (!response.ok) {
        pc.close();
        const error = new Error(`WHEP ${response.status}`);
        error.status = response.status;
        error.body = await response.text().catch(() => '');
        throw error;
    }

    const answer = await response.text();
    const resourceUrl = buildResourceUrl(uid, profile, response.headers.get('Location'));

    try {
        await pc.setRemoteDescription({ type: 'answer', sdp: answer });
        await waitForConnection(pc);
    } catch (e) {
        pc.close();
        throw e;
    }

    const session = { pc, stream, resourceUrl, uid, profile };

    openSessions.add(session);

    return session;
};

export const createWhepSession = async (uid) => {
    try {
        return await negotiate(uid, 'original');
    } catch (e) {
        if (e.status === 400 && String(e.body || '').includes('no compatible video codec')) {
            return negotiate(uid, 'transcoded');
        }

        throw e;
    }
};

export function closeWhepSession(session, notifyServer = false) {
    if (!session || session.closed) return;

    session.closed = true;
    openSessions.delete(session);

    if (notifyServer && session.resourceUrl) {
        fetch(session.resourceUrl, { method: 'DELETE', keepalive: true }).catch(() => {});
    }

    session.pc.close();
}
