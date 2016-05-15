<?php

namespace bjhale\FuzzyPhrase;

class FuzzyPhrase
{
    
    private $dict;

    private $debug;
    
    public function __construct($debug = false)
    {
        $this->dict = pspell_new('en','american',null,null,PSPELL_FAST);

        $this->debug = $debug;
    }

    /**
     * 
     *
     * @param $word
     * @return $this
     * @throws FuzzyPhraseDictionaryException
     */
    public function addWord($word)
    {
        //if split words and call recursively if there are spaces.
        if(strpos($word,' ')){
            $words = explode(' ',$word);
            foreach($words as $w){
                $this->addWord($w);
            }
            return $this;
        }

        //captures various pspell warnings and notices.
        $message = null;
        set_error_handler(function($errno, $errstr) use (&$message){
            $message = $errstr;
        });
        $status = pspell_add_to_session($this->dict,$word);
        restore_error_handler();

        //throw exception on failure
        if(!$status){
            throw new FuzzyPhraseDictionaryException($message);
        }

        return $this;

    }

    /**
     * Given an input phrase, gives a suggested phrase free of typos.
     *
     * @param $phrase string
     * @return string
     */
    public function didYouMean($phrase)
    {

        $phraseWords = $this->getPhraseWordSuggestions($phrase);

        $phraseWords = $this->filterPhraseWordSuggestions($phraseWords);

        $phraseWords = $this->sortPhraseWordSuggestions($phraseWords);

        if($this->debug){
            var_dump($phraseWords);
        }

        $suggestedPhrase = [];
        foreach($phraseWords as $phraseWord){
            $suggestedPhrase[] = $phraseWord['suggestions'][0]['word'];
        }

        $suggestedPhrase = implode(' ',$suggestedPhrase);

        return $suggestedPhrase;

    }

    private function getPhraseWordSuggestions($phrase)
    {

        //split phrase into component words
        $phraseWords = explode(' ',$phrase);

        $phraseWordSuggestions = [];
        foreach($phraseWords as $word){

            //get word suggestions
            $suggestions = pspell_suggest($this->dict, $word);

            //analyze each word suggestion
            $wordSuggestions = [];
            foreach($suggestions as $suggestion){

                $wordDistance = levenshtein($suggestion,$word);
                $toneDistance = levenshtein(metaphone($suggestion),metaphone($word));

                $wordSuggestions[] = [
                    'word' => $suggestion,
                    'similarity' => similar_text($suggestion,$word),
                    'word_distance' => $wordDistance,
                    'tone_distance' => $toneDistance,
                    'distance' => $wordDistance + $toneDistance,
                ];
            }

            //create final suggestions array
            $phraseWordSuggestions[] = [
                'original_word' => $word,
                'original_tone' => metaphone($word),
                'original_sound' => soundex($suggestion),
                'suggestions' => $wordSuggestions
            ];

        }

        return $phraseWordSuggestions;
    }

    /**
     * Sorts suggestions for each phrase word for a better natural language match.
     *
     * @param $phraseWords
     * @return mixed
     */
    private function sortPhraseWordSuggestions($phraseWords)
    {
        foreach($phraseWords as &$phraseWord){

            /*
             * Sort suggestions by distance, then word_distance, then tone distance, then similarity.
             */
            usort($phraseWord['suggestions'],function($a, $b){

                //total distance greater
                if($a['distance'] > $b['distance']){
                    return 1;
                } elseif($a['distance'] < $b['distance']){
                    return -1;
                } else {

                    if($a['word_distance'] = $b['word_distance']){
                        if($a['tone_distance'] < $b['tone_distance']){
                            return 1;
                        }
                        if($a['tone_distance'] > $b['tone_distance']){
                            return -1;
                        }
                    }

                    if($a['word_distance'] < $b['word_distance']){
                        return 1;
                    }

                    if($a['word_distance'] > $b['word_distance']){
                        return -1;
                    }

                    if($a['similarity'] < $b['similarity']){
                        return 1;
                    }
                    if($a['similarity'] > $b['similarity']){
                        return -1;
                    }

                    return 0;
                }


            });

        }

        return $phraseWords;

    }

    private function getSuggestionArrayCutoff(array $suggestions)
    {

        $distances = [];
        foreach($suggestions as $suggestion){
            $distances[] = $suggestion['distance'];
        }

        $count = count($distances);
        $sum = array_sum($distances);
        $avg = $sum / $count;

        return $avg;

    }

    private function filterPhraseWordSuggestions($phraseWordSuggestions)
    {

        foreach($phraseWordSuggestions as &$phraseWord){

            $cutoff = (int) $this->getSuggestionArrayCutoff($phraseWord['suggestions']);

            if(count($phraseWord['suggestions']) > 10){
                $phraseWord['suggestions'] = array_filter($phraseWord['suggestions'],function($var) use($cutoff) {

                    //Remove words with combined distance greater than average
                    if($var['distance'] > $cutoff){
                        return false;
                    }


                    return true;
                });
            }



        }

        return $phraseWordSuggestions;

    }
    
    
}

class FuzzyPhraseDictionaryException extends \Exception {}