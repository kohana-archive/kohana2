<?php

class Controller_Kobot extends Controller {

	public function index()
	{
		// Start a new bot
		$bot = new Kobot('irc.freenode.net');

		// Enable debugging
		$bog->log_level = 1;

		// Add triggers
		$bot->set_trigger('^goodnight, bot$', array($this, 'trigger_quit'))
		    ->set_trigger('^register(.+)?$', array($this, 'register'))
		    ->set_trigger('^tell (.+?) about (.+)$', array($this, 'trigger_say'))
		    ->set_trigger('^updates$', array($this, 'trigger_updates'))
		    ->set_trigger('^([r#])(\d+)$', array($this, 'trigger_trac'))
		    ->set_trigger('^[a-z_]+$', array($this, 'trigger_default'));

		// Login and join the default channel
		$bot->login('koboto', 'PhoenixRisingKO');
		$bot->join('#kohana-dev', 'codefest');
		$bot->read();
	}

	public function register(Kobot $bot, array $data, array $params)
	{
		if ($data['target'] === $bot->username)
		{
			// Send the message back to the sender
			$data['target'] = $data['sender'];

			if (isset($params[1]))
			{
				if (preg_match('/[^a-z\d]+/i', $params[1]))
				{
					// Send the confirmation message
					$message = 'You have been registered as '.$data['sender'];
				}
				else
				{
					// Send the registration error
					$message = 'Passwords may only contain letters and numbers';
				}
			}
			else
			{
				// Send the register usage, no password was supplied
				$message = 'Usage: register <password>';
			}
		}
		else
		{
			// Only allow registration in private messages
			$message = $data['sender'].': Send me a private message: /msg '.$bot->username.' register <password>';
		}

		// Send the response message
		$bot->send('PRIVMSG '.$data['target'].' :'.$message);
	}


	public function say_hi(Kobot $bot)
	{
		// Say hello!
		$bot->log(1, 'Just saying a timed hello!');

		// Only execute the timer once
		$bot->remove_timer(array($this, __FUNCTION__));
	}

	public function trigger_default(Kobot $bot, array $data, array $params)
	{
		if (function_exists($params[0]))
		{
			$bot->send('PRIVMSG '.$data['target'].' :'.$data['sender'].': http://php.net/'.$params[0]);
		}
	}

	public function trigger_quit(Kobot $bot, array $data)
	{
		$bot->quit('goodnight, '.$data['sender']);
	}

	public function trigger_say(Kobot $bot, array $data, array $params)
	{
		switch ($params[2])
		{
			case 'yourself':
				$bot->send('PRIVMSG '.$data['target'].' :Who wants to know? '.$params[1].'? HA!');
			break;
		}
	}

	public function trigger_trac(Kobot $bot, array $data, array $params)
	{
		switch ($params[1])
		{
			case '#':
				$type = 'Ticket';
				$url  = 'http://dev.kohanaphp.com/ticket/'.$params[2];
			break;
			case 'r':
				$type = 'Revision';
				$url  = 'http://dev.kohanaphp.com/changeset/'.$params[2];
			break;
		}

		if (remote::status($url) === 200)
		{
			$bot->send('PRIVMSG '.$data['target'].' :'.$type.' '.$params[2].', '.$url);
		}
	}

	public function trigger_updates(Kobot $bot, array $data)
	{
		if (($feed = Kohana::cache('svn_updates', 300)) === NULL)
		{
			// Load the feed
			$feed = feed::parse('http://dev.kohanaphp.com/timeline?changeset=on&max=3&daysback=90&format=rss');

			// Save the feed
			Kohana::cache_save('svn_updates', $feed, 300);
		}

		foreach ($feed as $item)
		{
			$bot->send('PRIVMSG '.$data['target'].' :'.strip_tags($item['description']).' '.$item['link']);
		}
	}

} // End