import PlayerFactory from './player-factory';
import EsMessenger from '../../common/messenger';
import DurationStorage from '../../common/duration-storage';

class Show {

  constructor(element) {
    let container = $(element);
    this.htmlDom = $(element);
    this.userId = container.data('userId');
    this.userName = container.data('userName');
    this.fileId = container.data('fileId');
    this.fileGlobalId = container.data('fileGlobalId');

    this.courseId = container.data('courseId');
    this.lessonId = container.data('lessonId');
    this.timelimit = container.data('timelimit');

    this.playerType = container.data('player');
    this.fileType = container.data('fileType');
    this.url = container.data('url');
    this.videoHeaderLength = container.data('videoHeaderLength');
    this.enablePlaybackRates = container.data('enablePlaybackRates');
    this.videoH5 = container.data('videoH5');
    this.watermark = container.data('watermark');
    this.accesskey = container.data('accessKey');
    this.fingerprint = container.data('fingerprint');
    this.fingerprintSrc = container.data('fingerprintSrc');
    this.fingerprintTime = container.data('fingerprintTime');
    this.balloonVideoPlayer = container.data('balloonVideoPlayer');
    this.markerUrl = container.data('markerurl');
    this.finishQuestionMarkerUrl = container.data('finishQuestionMarkerUrl');
    this.starttime = container.data('starttime');
    this.agentInWhiteList = container.data('agentInWhiteList');
    this.disableVolumeButton = container.data('disableVolumeButton');
    this.disablePlaybackButton = container.data('disablePlaybackButton');
    this.disableResolutionSwitcher = container.data('disableResolutionSwitcher');
    this.subtitles = container.data('subtitles');
    this.autoplay = container.data('autoplay');

    this.initView();
    this.initEvent();
  }

  initView() {
    let html = '';
    if (this.fileType == 'video') {
      if (this.playerType == 'local-video-player') {
        html += '<video id="lesson-player" style="width: 100%;height: 100%;" class="video-js vjs-default-skin" controls preload="auto"></video>';
      } else {
        html += '<div id="lesson-player" style="width: 100%;height: 100%;"></div>';
      }
    } else if (this.fileType == 'audio') {
      html += '<audio id="lesson-player" style="width: 100%;height: 100%;" class="video-js vjs-default-skin" controls preload="auto"></audio>';
    }
    this.htmlDom.html(html);
    this.htmlDom.show();
  }

  initPlayer() {
    return PlayerFactory.create(
      this.playerType, {
        element: '#lesson-player',
        url: this.url,
        mediaType: this.fileType,
        fingerprint: this.fingerprint,
        fingerprintSrc: this.fingerprintSrc,
        fingerprintTime: this.fingerprintTime,
        watermark: this.watermark,
        starttime: this.starttime,
        agentInWhiteList: this.agentInWhiteList,
        timelimit: this.timelimit,
        enablePlaybackRates: this.enablePlaybackRates,
        videoH5: this.videoH5,
        controlBar: {
          disableVolumeButton: this.disableVolumeButton,
          disablePlaybackButton: this.disablePlaybackButton,
          disableResolutionSwitcher: this.disableResolutionSwitcher
        },
        statsInfo: {
          accesskey: this.accesskey,
          globalId: this.fileGlobalId,
          userId: this.userId,
          userName: this.userName
        },
        resId: this.fileGlobalId,
        videoHeaderLength: this.videoHeaderLength,
        textTrack: this.transToTextrack(this.subtitles),
        autoplay: this.autoplay
      }
    );
  }

  transToTextrack(subtitles) {
    let textTracks = [];
    if (subtitles) {
      for (let i in subtitles) {
        let item = {
          label: subtitles[i].name,
          src: subtitles[i].url,
          'default': ('default' in subtitles[i]) ? subtitles[i]['default'] : false
        };
        textTracks.push(item);
      }
    }

    // set first item to default if no default
    for (let i in textTracks) {
      if (textTracks[i]['default']) {
        return;
      }
      textTracks[0]['default'] = true;
    }
    return textTracks;
  }

  initMesseger() {
    return new EsMessenger({
      name: 'parent',
      project: 'PlayerProject',
      type: 'child'
    });
  }

  isCloudPalyer() {
    return 'balloon-cloud-video-player' == this.playerType;
  }

  initEvent() {
    let player = this.initPlayer();
    let messenger = this.initMesseger();
    player.on('ready', () => {
      messenger.sendToParent('ready', {
        pause: true,
        currentTime: player.getCurrentTime()
      });
      if (!this.isCloudPalyer()) {
        let time = DurationStorage.get(this.userId, this.fileId);
        if (time > 0) {
          player.setCurrentTime(time);
        }
        player.play();
      } else if (this.isCloudPalyer()) {
        if (this.markerUrl) {
          $.getJSON(this.markerUrl, function(questions) {
            player.setQuestions(questions);
          });
        }
      }
    });

    player.on('answered', (data) => {
      let regExp = /course\/(\d+)\/task\/(\d+)\//;
      let matches = regExp.exec(window.location.href);

      if (matches) {
        $.post(this.finishQuestionMarkerUrl, {
          'questionMarkerId': data.questionMarkerId,
          'answer': data.userAnswers,
          'type': data.type,
          'courseId': matches[1],
          'taskId': matches[2],
        }, function(result) {

        });
      }

    });

    player.on('timechange', (data) => {
      messenger.sendToParent('timechange', {
        pause: true,
        currentTime: player.getCurrentTime()
      });
      if (!this.isCloudPalyer()) {
        if (parseInt(player.getCurrentTime()) != parseInt(player.getDuration())) {
          DurationStorage.set(this.userId, this.fileId, player.getCurrentTime());
        }
      }
    });

    player.on('paused', () => {
      messenger.sendToParent('paused', {
        pause: true,
        currentTime: player.getCurrentTime()
      });
    });

    player.on('playing', () => {
      messenger.sendToParent('playing', {
        pause: false,
        currentTime: player.getCurrentTime()
      });
    });

    player.on('ended', () => {
      messenger.sendToParent('ended', {
        stop: true
      });
      if (!this.isCloudPalyer()) {
        DurationStorage.del(this.userId, this.fileId);
      }
    });
  }


}
new Show('#lesson-video-content');
