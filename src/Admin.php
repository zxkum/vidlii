<?php
    namespace Vidlii\Vidlii;

    class Admin extends Engine {
        public function statistics($advanced = false) {
            $stats = [
                "users" => $this->DB->execute("SELECT count(*) as amount FROM users", true)["amount"],
                "videos" => $this->DB->execute("SELECT count(url) as amount FROM videos")[0]["amount"],
                "playlists" => $this->DB->execute("SELECT count(*) as amount FROM video_comments", true)["amount"],
                "comments" => $this->DB->execute("SELECT count(url) as amount FROM video_comments")[0]["amount"],
                "channel_comments" => $this->DB->execute("SELECT count(*) as amount FROM channel_comments", true)["amount"],
                "favorites" => $this->DB->execute("SELECT count(url) as amount FROM video_favorites")[0]["amount"],
                "responses" => $this->DB->execute("SELECT count(id) as amount FROM video_responses")[0]["amount"]
            ];
            if($advanced) {
                $stats += [
                    //"suggestions" => $this->DB->execute("SELECT * FROM feature_suggestions INNER JOIN users ON feature_suggestions.from_user = users.username ORDER BY id DESC"),
                    //"bulletins" => $this->DB->execute("SELECT * FROM bulletins INNER JOIN users ON users.username = bulletins.by_user ORDER BY date DESC LIMIT 20"),
                    //"converting" => $this->DB->execute("SELECT url, uploaded_on, convert_status FROM converting"),
                    "channels" => [
                        "channel_1" => $this->DB->execute("SELECT count(*) as amount FROM users WHERE channel_version = 1", true)["amount"],
                        "channel_2" => $this->DB->execute("SELECT count(*) as amount FROM users WHERE channel_version = 2", true)["amount"],
                        "channel_3" => $this->DB->execute("SELECT count(*) as amount FROM users WHERE channel_version = 3", true)["amount"],
                    ],
                    "partners" => $this->DB->execute("SELECT count(*) as amount FROM users WHERE partner = 1", true)["amount"],
                    "watched" => $this->DB->execute("SELECT sum(videos_watched) as amount FROM users", true)["amount"],
                    "channel_views" => $this->DB->execute("SELECT sum(channel_views) as amount FROM users", true)["amount"],
                    "subscriptions" => $this->DB->execute("SELECT sum(subscriptions) as amount FROM users", true)["amount"],
                    "favorites" => $this->DB->execute("SELECT sum(favorites) as amount FROM users", true)["amount"],
                    "views" => $this->DB->execute("SELECT sum(displayviews) as amount FROM videos", true)["amount"],
                    "video_comments" => $this->DB->execute("SELECT count(*) as amount FROM video_comments", true)["amount"],
                    "watchtime" => $this->DB->execute("SELECT sum(watched) as amount FROM videos", true)["amount"] / 60,
                ];
            }
            return $stats;
        }
        public function logins() {
            return $this->DB->execute("select * from wrong_logins");
        }
    }
?>